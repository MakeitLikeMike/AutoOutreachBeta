 <?php
require_once 'classes/Campaign.php';
require_once 'classes/TargetDomain.php';
require_once 'classes/ApiIntegration.php';
require_once 'classes/EmailTemplate.php';

$campaign = new Campaign();
$targetDomain = new TargetDomain();
$api = new ApiIntegration();
$emailTemplate = new EmailTemplate();

$action = $_GET['action'] ?? 'list';
$campaignId = $_GET['id'] ?? null;
$message = '';
$error = '';

function isSpamDomain($domain) {
    $spamPatterns = [
        'seo-anomaly',
        'seo-analitycs',
        'seo-analytics',
        'seoanalytics',
        'fake-seo',
        'spam-seo',
        'seo-spam',
        'link-farm',
        'linkfarm',
        'pbn-',
        '-pbn',
        'private-blog-network',
        'automated-seo',
        'bot-seo',
        'fake-traffic',
        'traffic-bot',
        'seo-tools',
        'tool-seo',
        'bulk-seo',
        'mass-seo',
        'auto-seo',
        'generated-',
        'dummy-',
        'test-site', 
        'placeholder',
        'sample-site'
    ];

    $domain = strtolower($domain);

    foreach ($spamPatterns as $pattern) {
        if (strpos($domain, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function createDomainsForBackgroundProcessing($campaignId, $competitor_urls) {

    global $api, $targetDomain;

        require_once 'config/database.php';
    $db = new Database();

    $urls = array_filter(array_map('trim', explode("\n", $competitor_urls)));
    $totalDomainsCreated = 0;

    foreach ($urls as $url) {
        try {
                        if (!preg_match('/^https?:\/\//', $url)) {
                $url = 'https://' . $url;
            }

                        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

                        $backlinkResults = $api->fetchBacklinks($url);

            if (!empty($backlinkResults)) {
                                $domains = [];
                if (isset($backlinkResults[0]['items']) && is_array($backlinkResults[0]['items'])) {
                    foreach ($backlinkResults[0]['items'] as $backlink) {
                        if (isset($backlink['domain_from']) && isset($backlink['domain_from_rank'])) {
                            $domain = $backlink['domain_from'];
                            $domainRating = $backlink['domain_from_rank'] ?? 0;

                                                        if ($domainRating > 30 && !isSpamDomain($domain)) {
                                $domains[] = $domain;
                            }
                        }
                    }
                }

                                $domains = array_unique($domains);

                foreach ($domains as $domain) {
                    if (is_string($domain) && !empty($domain)) {
                                                $basicMetrics = [
                            'domain_rating' => 0,
                            'organic_traffic' => 0,
                            'referring_domains' => 0,
                            'ranking_keywords' => 0,
                            'quality_score' => 0,
                            'contact_email' => null
                        ];

                        $targetDomain->create($campaignId, $domain, $basicMetrics);
                        $totalDomainsCreated++;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error creating domains for campaign $campaignId: " . $e->getMessage());
        }
    }

        $sql = "UPDATE campaigns SET domains_processed = 0, domains_found = ? WHERE id = ?";
    $db->execute($sql, [$totalDomainsCreated, $campaignId]);

    return $totalDomainsCreated;
}

if ($_POST) {
    try {
        if ($action === 'create') {
            $name = $_POST['name'];
            $competitor_urls = $_POST['competitor_urls'];
            $owner_email = $_POST['owner_email'];
            $automation_mode = $_POST['automation_mode'];
            $email_template_id = $_POST['email_template_id'] ?? null;
            $auto_send = isset($_POST['auto_send']) ? 1 : 0;

                        $auto_domain_analysis = isset($_POST['auto_domain_analysis']) ? 1 : 0;
            $auto_email_search = isset($_POST['auto_email_search']) ? 1 : 0;
            $auto_reply_monitoring = isset($_POST['auto_reply_monitoring']) ? 1 : 0;
            $auto_lead_forwarding = isset($_POST['auto_lead_forwarding']) ? 1 : 0;

                        try {
                                $automation_settings = [
                    'auto_domain_analysis' => $auto_domain_analysis,
                    'auto_email_search' => $auto_email_search,
                    'auto_reply_monitoring' => $auto_reply_monitoring,
                    'auto_lead_forwarding' => $auto_lead_forwarding
                ];

                $id = $campaign->create($name, $competitor_urls, $owner_email, $email_template_id, $automation_mode, $auto_send, $automation_settings);
            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    $id = $campaign->createBasic($name, $competitor_urls);
                } else {
                    throw $e;
                }
            }

                        if (!empty($competitor_urls)) {
                                require_once 'classes/BackgroundJobProcessor.php';
                $processor = new BackgroundJobProcessor();

                $processor->queueJob('fetch_backlinks', $id, null, [
                    'competitor_urls' => $competitor_urls
                ], 10);
                $message = "Campaign created successfully! 🚀 Automated processing has started. You'll receive qualified leads directly in your inbox as they're found.";
            } else {
                $message = "Campaign created successfully! Add competitor URLs to begin automated outreach.";
            }

            $action = 'list';
        } elseif ($action === 'update' && $campaignId) {
            $name = $_POST['name'];
            $competitor_urls = $_POST['competitor_urls'];
            $owner_email = $_POST['owner_email'];
            $status = $_POST['status'];
            $email_template_id = $_POST['email_template_id'] ?? null;

            $campaign->update($campaignId, $name, $competitor_urls, $owner_email, $status, $email_template_id);
            $message = "Campaign updated successfully!";
            $action = 'list';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($action === 'delete' && $campaignId) {
    try {
        $campaign->delete($campaignId);
        $message = "Campaign deleted successfully!";
        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($action === 'bulk_delete' && isset($_POST['campaign_ids'])) {
    try {
        $campaignIds = $_POST['campaign_ids'];
        $deletedCount = 0;
        $errors = [];

        if (empty($campaignIds)) {
            throw new Exception("No campaigns selected for deletion");
        }

        foreach ($campaignIds as $id) {
            if (is_numeric($id)) {
                try {
                    $campaign->delete($id);
                    $deletedCount++;
                } catch (Exception $e) {
                    $errors[] = "Failed to delete campaign ID $id: " . $e->getMessage();
                }
            }
        }

        if ($deletedCount > 0) {
            $message = "$deletedCount campaign(s) deleted successfully!";
            if (!empty($errors)) {
                $message .= " However, some deletions failed: " . implode(", ", $errors);
            }
        } else {
            $error = "No campaigns were deleted. " . implode(", ", $errors);
        }

        $action = 'list';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$campaigns = $campaign->getAll();
$emailTemplates = $emailTemplate->getAll();
$currentCampaign = null;
if ($campaignId && in_array($action, ['edit', 'view'])) {
    $currentCampaign = $campaign->getById($campaignId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - Outreach Automation</title>
    <link rel="icon" type="image/png" href="logo/logo.png">
    <link rel="stylesheet" href="assets/css/campaigns.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button onclick="goBack()" class="back-btn" title="Go Back">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1>
                    <?php
                    switch ($action) {
                        case 'new':
                        case 'create':
                            echo 'Create New Campaign';
                            break;
                        case 'edit':
                            echo 'Edit Campaign';
                            break;
                        case 'view':
                            echo 'Campaign Details';
                            break;
                        default:
                            echo 'Campaign Management';
                    }
                    ?>
                </h1>
            </div>
        </header>

        <div style="padding: 2rem;">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn"></i> All Campaigns</h3>
                        <div class="actions">
                            <button id="bulk-delete-btn" class="btn btn-danger" style="display: none;" onclick="confirmBulkDelete()">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                            <a href="campaigns.php?action=new" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Campaign
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($campaigns)): ?>
                            <form id="bulk-delete-form" method="POST" action="campaigns.php?action=bulk_delete">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all" onchange="toggleSelectAll()" title="Select All">
                                        </th>
                                        <th>Campaign Name</th>
                                        <th>Pipeline Status</th>
                                        <th>Progress</th>
                                        <th>Domains</th>
                                        <th>Emails Sent</th>
                                        <th>Qualified Leads</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $camp): ?>
                                        <tr class="clickable-row" onclick="handleRowClick(event, <?php echo $camp['id']; ?>)">
                                            <td onclick="event.stopPropagation();">
                                                <input type="checkbox" name="campaign_ids[]" value="<?php echo $camp['id']; ?>" class="campaign-checkbox" onchange="updateBulkDeleteButton()">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($camp['name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($camp['owner_email'] ?? 'No owner email'); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $pipelineStatus = $camp['pipeline_status'] ?? 'created';
                                                $statusLabels = [
                                                    'created' => '📋 Created',
                                                    'processing_domains' => '🔍 Finding Domains',
                                                    'analyzing_quality' => '⚖️ Analyzing Quality',
                                                    'finding_emails' => '📧 Finding Emails',
                                                    'sending_outreach' => '📤 Sending Outreach',
                                                    'monitoring_replies' => '👀 Monitoring Replies',
                                                    'completed' => '✅ Complete'
                                                ];
                                                ?>
                                                <span class="pipeline-status status-<?php echo $pipelineStatus; ?>">
                                                    <?php echo $statusLabels[$pipelineStatus] ?? ucfirst($pipelineStatus); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $progress = $camp['progress_percentage'] ?? 0;
                                                $progressColor = $progress >= 75 ? '#10b981' : ($progress >= 50 ? '#f59e0b' : '#ef4444');
                                                ?>
                                                <div class="progress-bar" title="Campaign Progress: <?php echo $progress; ?>%">
                                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $progressColor; ?>"></div>
                                                </div>
                                                <small><?php echo $progress; ?>% complete</small>
                                            </td>
                                            <td>
                                                <div class="metric-stack">
                                                    <div><strong><?php echo $camp['total_domains_scraped'] ?? $camp['total_domains'] ?? 0; ?></strong> <small>found</small></div>
                                                    <div><strong><?php echo $camp['approved_domains_count'] ?? $camp['approved_domains'] ?? 0; ?></strong> <small>approved</small></div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo $camp['emails_sent_count'] ?? 0; ?></strong>
                                                <?php if ($camp['emails_found_count'] ?? 0 > 0): ?>
                                                    <br><small class="text-muted"><?php echo $camp['emails_found_count']; ?> emails found</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $camp['qualified_leads_count'] ?? 0; ?></strong>
                                                <?php if ($camp['replies_received_count'] ?? 0 > 0): ?>
                                                    <br><small class="text-muted"><?php echo $camp['replies_received_count']; ?> replies</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($camp['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-bullhorn"></i>
                                <p>No campaigns created yet.</p>
                                <a href="campaigns.php?action=new" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Your First Campaign
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'new' || $action === 'create'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-plus"></i> Create New Campaign</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="campaigns.php?action=create">
                            <div class="form-group">
                                <label for="name">Campaign Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       placeholder="e.g., Technology Guest Posts Q1 2024">
                            </div>

                            <div class="form-group">
                                <label for="owner_email">Campaign Owner Email *</label>
                                <input type="email" id="owner_email" name="owner_email" class="form-control" required
                                       placeholder="owner@yourcompany.com">
                                <small class="help-text">Email where done deal outreach will be forwarded for publication coordination</small>
                            </div>

                            <div class="form-group">
                                <label for="automation_mode">Email Generation Mode *</label>
                                <select id="automation_mode" name="automation_mode" class="form-control" required>
                                    <option value="">Select email generation mode</option>
                                    <option value="auto_generate">Auto-Generate Outreach Emails (AI-powered)</option>
                                    <option value="template">Use Provided Email Template</option>
                                </select>
                                <small class="help-text">Choose how outreach emails will be generated for this campaign</small>
                            </div>

                            <div class="form-group" id="template_selection" style="display: none;">
                                <label for="email_template_id">Email Template</label>
                                <select id="email_template_id" name="email_template_id" class="form-control">
                                    <option value="">Select an email template</option>
                                    <?php foreach ($emailTemplates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>" <?php echo $template['is_default'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                            <?php echo $template['is_default'] ? ' (Default)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="help-text">Choose an email template for outreach emails in this campaign</small>
                            </div>

                            <div class="form-group">
                                <label for="automation_settings">🤖 Full Automation Settings</label>
                                <div class="automation-panel">
                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="auto_domain_analysis" name="auto_domain_analysis" value="1" checked>
                                            <span class="checkmark"></span>
                                            <strong>Auto-analyze domains</strong> - Automatically score and approve/reject domains based on quality metrics
                                        </label>
                                    </div>

                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="auto_email_search" name="auto_email_search" value="1" checked>
                                            <span class="checkmark"></span>
                                            <strong>Auto-find contact emails</strong> - Automatically search for contact emails using Tomba API
                                        </label>
                                    </div>

                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="auto_send" name="auto_send" value="1" checked>
                                            <span class="checkmark"></span>
                                            <strong>Auto-send outreach emails</strong> - Automatically send personalized emails to found contacts
                                        </label>
                                    </div>

                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="auto_reply_monitoring" name="auto_reply_monitoring" value="1" checked>
                                            <span class="checkmark"></span>
                                            <strong>Auto-monitor replies</strong> - Automatically monitor and classify incoming replies
                                        </label>
                                    </div>

                                    <div class="checkbox-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" id="auto_lead_forwarding" name="auto_lead_forwarding" value="1" checked>
                                            <span class="checkmark"></span>
                                            <strong>Auto-forward qualified leads</strong> - Automatically forward positive replies to campaign owner
                                        </label>
                                    </div>

                                    <div class="automation-summary">
                                        <div class="alert alert-info">
                                            <i class="fas fa-robot"></i>
                                            <strong>Full Automation Enabled:</strong> With all options selected, you only need to provide campaign details and competitor URLs.
                                            The system will handle everything from domain analysis to delivering qualified leads to your inbox.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="competitor_urls">Competitor URLs</label>
                                <textarea id="competitor_urls" name="competitor_urls" class="form-control textarea"
                                          placeholder="Enter competitor URLs (one per line)&#10;example1.com&#10;https://example2.com&#10;competitor3.com"></textarea>
                                <small class="help-text">Enter competitor URLs to analyze their backlinks. One URL per line. HTTPS will be added automatically if no protocol is specified.</small>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Campaign
                                </button>
                                <a href="campaigns.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'edit' && $currentCampaign): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Edit Campaign</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="campaigns.php?action=update&id=<?php echo $currentCampaign['id']; ?>">
                            <div class="form-group">
                                <label for="name">Campaign Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($currentCampaign['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="owner_email">Campaign Owner Email *</label>
                                <input type="email" id="owner_email" name="owner_email" class="form-control" required
                                       value="<?php echo htmlspecialchars($currentCampaign['owner_email'] ?? ''); ?>">
                                <small class="help-text">Email where done deal outreach will be forwarded for publication coordination</small>
                            </div>

                            <div class="form-group">
                                <label for="email_template_id">Email Template</label>
                                <select id="email_template_id" name="email_template_id" class="form-control">
                                    <option value="">Select an email template (optional)</option>
                                    <?php foreach ($emailTemplates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>"
                                                <?php echo (($currentCampaign['email_template_id'] ?? null) == $template['id'] || (empty($currentCampaign['email_template_id']) && $template['is_default'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($template['name']); ?>
                                            <?php echo $template['is_default'] ? ' (Default)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="help-text">Choose an email template for outreach emails in this campaign</small>
                            </div>

                            <div class="form-group">
                                <label for="competitor_urls">Competitor URLs</label>
                                <textarea id="competitor_urls" name="competitor_urls" class="form-control textarea"><?php echo htmlspecialchars($currentCampaign['competitor_urls']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active" <?php echo $currentCampaign['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="paused" <?php echo $currentCampaign['status'] === 'paused' ? 'selected' : ''; ?>>Paused</option>
                                    <option value="completed" <?php echo $currentCampaign['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Campaign
                                </button>
                                <a href="campaigns.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'view' && $currentCampaign): ?>
                <?php
                $stats = $campaign->getStats($currentCampaign['id']);
                $domains = $targetDomain->getByCampaign($currentCampaign['id']);
                ?>
                <div class="content-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Campaign Details</h3>
                            <div class="actions">
                                <a href="campaigns.php?action=edit&id=<?php echo $currentCampaign['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Edit Campaign
                                </a>
                                <a href="campaigns.php?action=delete&id=<?php echo $currentCampaign['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this campaign?')">
                                    <i class="fas fa-trash"></i> Delete Campaign
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <strong>Name:</strong> <?php echo htmlspecialchars($currentCampaign['name']); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Owner Email:</strong> <?php echo htmlspecialchars($currentCampaign['owner_email'] ?? 'Not set'); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Status:</strong>
                                <span class="status status-<?php echo $currentCampaign['status']; ?>">
                                    <?php echo ucfirst($currentCampaign['status']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($currentCampaign['created_at'])); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($currentCampaign['updated_at'])); ?>
                            </div>
                            <?php if ($currentCampaign['is_automated'] ?? 0): ?>
                            <div class="detail-item">
                                <strong>Automation:</strong>
                                <span class="badge badge-success">
                                    <i class="fas fa-robot"></i> Fully Automated
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Email Mode:</strong> <?php echo ucwords(str_replace('_', ' ', $currentCampaign['automation_mode'])); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Auto Send:</strong> <?php echo $currentCampaign['auto_send'] ? 'Enabled' : 'Disabled'; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['total_domains'] ?? 0; ?></div>
                                    <div class="stat-label">Total Domains</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
                                    <div class="stat-label">Approved</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $stats['contacted'] ?? 0; ?></div>
                                    <div class="stat-label">Contacted</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($stats['avg_quality_score'] ?? 0, 1); ?></div>
                                    <div class="stat-label">Avg Quality</div>
                                </div>
                                <?php if ($currentCampaign['is_automated'] ?? 0): ?>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $currentCampaign['emails_sent'] ?? 0; ?></div>
                                    <div class="stat-label">Emails Sent</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $currentCampaign['leads_forwarded'] ?? 0; ?></div>
                                    <div class="stat-label">Leads Generated</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3><i class="fas fa-globe"></i> Target Domains (<?php echo count($domains); ?>)</h3>
                        <div class="actions">
                            <a href="domains.php?campaign_id=<?php echo $currentCampaign['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-search"></i> Analyze More Domains
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($domains)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Domain</th>
                                        <th>DR</th>
                                        <th>Traffic</th>
                                        <th>Quality</th>
                                        <th>Status</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($domains, 0, 10) as $domain): ?>
                                        <tr class="clickable-row" onclick="window.location.href='domains.php?action=view&id=<?php echo $domain['id']; ?>'" style="cursor: pointer;">
                                            <td>
                                                <strong><?php echo htmlspecialchars($domain['domain']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="domain-rating"><?php echo $domain['domain_rating'] ?? 'N/A'; ?></span>
                                            </td>
                                            <td><?php echo number_format($domain['organic_traffic'] ?? 0); ?></td>
                                            <td><?php echo number_format($domain['quality_score'], 1); ?></td>
                                            <td>
                                                <span class="status status-<?php echo $domain['status']; ?>">
                                                    <?php echo ucfirst($domain['status']); ?>
                                                </span>
                                            </td>
                                            <td onclick="event.stopPropagation();">
                                                <?php if ($domain['contact_email']): ?>
                                                    <a href="mailto:<?php echo $domain['contact_email']; ?>">
                                                        <?php echo $domain['contact_email']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Not found</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (count($domains) > 10): ?>
                                <div class="text-center mt-3">
                                    <a href="domains.php?campaign_id=<?php echo $currentCampaign['id']; ?>" class="btn btn-secondary">
                                        View All <?php echo count($domains); ?> Domains
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-globe"></i>
                                <p>No domains found for this campaign.</p>
                                <a href="domains.php?campaign_id=<?php echo $currentCampaign['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Start Domain Analysis
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/campaigns.js"></script>
</body>
</html>