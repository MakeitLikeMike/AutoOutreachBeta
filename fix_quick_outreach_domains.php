<?php
require_once 'config/database.php';
require_once 'classes/SystemLogger.php';

class QuickOutreachDomainFixer {
    private $db;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->logger = new SystemLogger();
    }
    
    /**
     * Create a target_domains record for quick outreach
     */
    public function createQuickOutreachDomain($campaignId, $domain, $contactEmail) {
        try {
            $this->logger->logDatabaseOperation('INSERT', 'target_domains', [
                'campaign_id' => $campaignId,
                'domain' => $domain,
                'contact_email' => $contactEmail
            ]);
            
            // Check if domain already exists for this campaign
            $checkSql = "SELECT id FROM target_domains WHERE campaign_id = ? AND domain = ?";
            $stmt = $this->db->prepare($checkSql);
            $stmt->execute([$campaignId, $domain]);
            $existingDomain = $stmt->fetch();
            
            if ($existingDomain) {
                $this->logger->logInfo('QUICK_OUTREACH', 'Domain already exists for campaign', [
                    'domain_id' => $existingDomain['id'],
                    'campaign_id' => $campaignId,
                    'domain' => $domain
                ]);
                return $existingDomain['id'];
            }
            
            // Create new target_domains record
            $insertSql = "INSERT INTO target_domains (
                campaign_id, domain, contact_email, status, quality_score, 
                domain_rating, organic_traffic, ranking_keywords, 
                email_search_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($insertSql);
            $stmt->execute([
                $campaignId,
                $domain,
                $contactEmail,
                'contacted', // Mark as contacted since we're sending email
                75, // Default quality score for quick outreach
                50, // Default domain rating
                10000, // Default organic traffic
                100, // Default ranking keywords
                'found' // Email was found via quick outreach
            ]);
            
            $domainId = $this->db->lastInsertId();
            
            $this->logger->logInfo('QUICK_OUTREACH', 'Created domain record for quick outreach', [
                'domain_id' => $domainId,
                'campaign_id' => $campaignId,
                'domain' => $domain,
                'contact_email' => $contactEmail
            ]);
            
            return $domainId;
            
        } catch (Exception $e) {
            $this->logger->logError('QUICK_OUTREACH', 'Failed to create domain record', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId,
                'domain' => $domain
            ]);
            throw $e;
        }
    }
    
    /**
     * Update existing outreach_emails records with NULL domain_id
     */
    public function fixExistingQuickOutreachEmails() {
        try {
            $this->logger->logInfo('MIGRATION', 'Starting fix for existing quick outreach emails');
            
            // Find outreach_emails with NULL domain_id
            $findSql = "SELECT id, campaign_id, recipient_email FROM outreach_emails WHERE domain_id IS NULL";
            $stmt = $this->db->prepare($findSql);
            $stmt->execute();
            $orphanEmails = $stmt->fetchAll();
            
            $fixedCount = 0;
            foreach ($orphanEmails as $email) {
                // Extract domain from recipient_email
                $domain = $this->extractDomainFromEmail($email['recipient_email']);
                
                if ($domain) {
                    // Create or find domain record
                    $domainId = $this->createQuickOutreachDomain(
                        $email['campaign_id'], 
                        $domain, 
                        $email['recipient_email']
                    );
                    
                    // Update outreach_emails record
                    $updateSql = "UPDATE outreach_emails SET domain_id = ? WHERE id = ?";
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([$domainId, $email['id']]);
                    
                    $fixedCount++;
                    
                    $this->logger->logInfo('MIGRATION', 'Fixed orphan email record', [
                        'email_id' => $email['id'],
                        'domain_id' => $domainId,
                        'domain' => $domain
                    ]);
                }
            }
            
            $this->logger->logInfo('MIGRATION', 'Completed fixing orphan email records', [
                'total_orphans' => count($orphanEmails),
                'fixed_count' => $fixedCount
            ]);
            
            return $fixedCount;
            
        } catch (Exception $e) {
            $this->logger->logError('MIGRATION', 'Failed to fix orphan email records', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function extractDomainFromEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return substr(strrchr($email, "@"), 1);
        }
        return null;
    }
    
    /**
     * Update campaign metrics to include quick outreach emails
     */
    public function updateCampaignMetrics($campaignId = null) {
        try {
            $whereClause = $campaignId ? "WHERE c.id = ?" : "";
            $params = $campaignId ? [$campaignId] : [];
            
            $updateSql = "
                UPDATE campaigns c
                SET 
                    emails_sent_count = (
                        SELECT COUNT(*) FROM outreach_emails oe 
                        WHERE oe.campaign_id = c.id AND oe.status = 'sent'
                    ),
                    total_domains_scraped = (
                        SELECT COUNT(*) FROM target_domains td 
                        WHERE td.campaign_id = c.id
                    ),
                    approved_domains_count = (
                        SELECT COUNT(*) FROM target_domains td 
                        WHERE td.campaign_id = c.id AND td.status IN ('approved', 'contacted')
                    ),
                    emails_found_count = (
                        SELECT COUNT(*) FROM target_domains td 
                        WHERE td.campaign_id = c.id AND td.contact_email IS NOT NULL
                    )
                {$whereClause}
            ";
            
            $stmt = $this->db->prepare($updateSql);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            
            $this->logger->logInfo('MIGRATION', 'Updated campaign metrics', [
                'campaign_id' => $campaignId,
                'affected_rows' => $affectedRows
            ]);
            
            return $affectedRows;
            
        } catch (Exception $e) {
            $this->logger->logError('MIGRATION', 'Failed to update campaign metrics', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaignId
            ]);
            throw $e;
        }
    }
}

// Run the fix if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $fixer = new QuickOutreachDomainFixer();
        
        echo "=== Quick Outreach Domain Fix ===\n";
        echo "1. Fixing existing orphan email records...\n";
        $fixedCount = $fixer->fixExistingQuickOutreachEmails();
        echo "Fixed {$fixedCount} orphan email records.\n\n";
        
        echo "2. Updating campaign metrics...\n";
        $updatedCampaigns = $fixer->updateCampaignMetrics();
        echo "Updated metrics for {$updatedCampaigns} campaigns.\n\n";
        
        echo "Fix completed successfully! ✅\n";
        
    } catch (Exception $e) {
        echo "❌ Error during fix: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>