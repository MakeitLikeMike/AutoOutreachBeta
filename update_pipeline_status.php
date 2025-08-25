<?php
/**
 * Manual Pipeline Status Update Script
 * Run this to immediately update all campaign statuses
 */
require_once 'config/database.php';
require_once 'classes/PipelineStatusUpdater.php';

echo "🔄 Updating Pipeline Status for All Campaigns\n";
echo "==============================================\n\n";

try {
    $updater = new PipelineStatusUpdater();
    
    // Get all campaigns
    $db = new Database();
    $campaigns = $db->fetchAll("SELECT id, name, pipeline_status FROM campaigns ORDER BY created_at DESC");
    
    echo "Found " . count($campaigns) . " campaigns to update:\n\n";
    
    foreach ($campaigns as $campaign) {
        echo "📋 Campaign: " . $campaign['name'] . "\n";
        echo "   Current Status: " . $campaign['pipeline_status'] . "\n";
        
        $result = $updater->updateCampaignStatus($campaign['id']);
        
        echo "   New Status: " . $result['status'] . "\n";
        echo "   Progress: " . $result['progress'] . "%\n";
        echo "   Stats: " . $result['stats']['total_domains'] . " domains, " . 
             $result['stats']['emails_sent'] . " emails sent\n\n";
    }
    
    echo "✅ All campaigns updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>