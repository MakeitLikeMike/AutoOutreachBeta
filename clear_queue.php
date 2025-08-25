<?php
require_once 'config/database.php';

echo "<h1>🧹 Queue Cleanup</h1>\n";

try {
    $db = new Database();
    
    echo "<h2>Current Queue Status:</h2>\n";
    $queueStats = $db->fetchAll("SELECT status, COUNT(*) as count FROM email_queue GROUP BY status");
    foreach ($queueStats as $stat) {
        echo "- {$stat['status']}: {$stat['count']} emails<br>\n";
    }
    
    echo "<h2>Updating Queue Entries:</h2>\n";
    
    // Mark existing queue entries as automation-triggered
    $result = $db->execute("UPDATE email_queue SET automation_triggered = 1 WHERE automation_triggered = 0");
    echo "✅ Updated existing queue entries to automation_triggered = 1<br>\n";
    
    // Clear old queued entries to allow reprocessing
    $result = $db->execute("DELETE FROM email_queue WHERE status = 'queued' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    echo "✅ Cleared old queued entries<br>\n";
    
    echo "<h2>Updated Queue Status:</h2>\n";
    $queueStats = $db->fetchAll("SELECT status, automation_triggered, COUNT(*) as count FROM email_queue GROUP BY status, automation_triggered");
    foreach ($queueStats as $stat) {
        $auto = $stat['automation_triggered'] ? 'Auto' : 'Manual';
        echo "- {$stat['status']} ({$auto}): {$stat['count']} emails<br>\n";
    }
    
    echo "<h2>✅ Queue Cleanup Complete!</h2>\n";
    echo "<strong>Next Steps:</strong><br>\n";
    echo "1. Connect active Gmail accounts in Settings<br>\n";
    echo "2. Visit the automation dashboard to trigger outreach<br>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>\n";
    echo "Error: " . $e->getMessage() . "<br>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
h2 { color: #374151; margin-top: 30px; margin-bottom: 15px; }
br { line-height: 1.6; }
</style>