<?php
header('Content-Type: application/json');

try {
    require_once 'config/database.php';
    
    $db = new Database();
    $jobsCreated = 0;
    
    // Find domains that are stuck in processing states
    $stuckDomains = $db->fetchAll("
        SELECT id, campaign_id, domain, status 
        FROM target_domains 
        WHERE status IN ('searching_email', 'sending_email', 'generating_email') 
        ORDER BY campaign_id, id
    ");
    
    foreach ($stuckDomains as $domain) {
        $jobType = '';
        $priority = 5;
        
        switch ($domain['status']) {
            case 'searching_email':
                $jobType = 'search_contact_email';
                break;
            case 'generating_email':
                $jobType = 'generate_outreach_email';
                break;
            case 'sending_email':
                $jobType = 'send_outreach_email';
                break;
            default:
                continue 2; // Skip this domain
        }
        
        // Check if job already exists for this domain
        $existingJob = $db->fetchOne("
            SELECT id FROM background_jobs 
            WHERE job_type = ? AND domain_id = ? AND status IN ('pending', 'processing')
        ", [$jobType, $domain['id']]);
        
        if (!$existingJob && !empty($jobType)) {
            // Create new background job
            $db->execute("
                INSERT INTO background_jobs (
                    job_type, campaign_id, domain_id, priority, status, created_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW())
            ", [$jobType, $domain['campaign_id'], $domain['id'], $priority]);
            
            $jobsCreated++;
        }
    }
    
    // Also create jobs for campaigns stuck in analyzing_quality
    $stuckCampaigns = $db->fetchAll("
        SELECT id FROM campaigns 
        WHERE pipeline_status = 'analyzing_quality' 
        AND status = 'active'
    ");
    
    foreach ($stuckCampaigns as $campaign) {
        // Check if there are domains waiting to be analyzed
        $pendingDomains = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM target_domains 
            WHERE campaign_id = ? AND ai_analysis_status IN ('pending', 'analyzing')
        ", [$campaign['id']]);
        
        if ($pendingDomains && $pendingDomains['count'] > 0) {
            // Check if job already exists
            $existingJob = $db->fetchOne("
                SELECT id FROM background_jobs 
                WHERE job_type = 'analyze_domain_quality' AND campaign_id = ? AND status IN ('pending', 'processing')
            ", [$campaign['id']]);
            
            if (!$existingJob) {
                $db->execute("
                    INSERT INTO background_jobs (
                        job_type, campaign_id, priority, status, created_at
                    ) VALUES ('analyze_domain_quality', ?, 8, 'pending', NOW())
                ", [$campaign['id']]);
                
                $jobsCreated++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'jobs_created' => $jobsCreated,
        'message' => "Created $jobsCreated background jobs for stuck campaigns/domains"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>