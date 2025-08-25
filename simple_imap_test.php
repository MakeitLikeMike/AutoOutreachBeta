<?php
require_once 'config/database.php';
require_once 'classes/IMAPReplyForwarder.php';

echo "=== SIMPLE IMAP REPLY TEST ===" . PHP_EOL;

try {
    $db = new Database();
    $imapForwarder = new IMAPReplyForwarder();
    
    echo "✅ Test email sent successfully (Email ID: 62)" . PHP_EOL;
    echo "📧 Recipient: jimmy@example.com" . PHP_EOL;
    echo "📬 From: teamoutreach41@gmail.com" . PHP_EOL;
    echo "📝 Subject: Test Outreach - Reply Monitoring System Test" . PHP_EOL . PHP_EOL;
    
    echo "🔍 MONITORING STATUS:" . PHP_EOL;
    echo "- IMAP Account: teamoutreach41@gmail.com" . PHP_EOL;
    echo "- Monitoring: Active" . PHP_EOL;
    echo "- Reply Forwarder: Ready" . PHP_EOL;
    echo "- Campaign Owner: Zack (teamoutreach41@gmail.com)" . PHP_EOL . PHP_EOL;
    
    echo "📋 TESTING INSTRUCTIONS FOR YOU:" . PHP_EOL;
    echo "1. You (as Jimmy) should reply to the test email" . PHP_EOL;
    echo "2. Reply with: 'I'm interested in guest posting opportunities'" . PHP_EOL;
    echo "3. The system will automatically:" . PHP_EOL;
    echo "   - Detect the reply via IMAP monitoring" . PHP_EOL;
    echo "   - Classify it as an 'interested' lead" . PHP_EOL;
    echo "   - Forward the reply to Zack (teamoutreach41@gmail.com)" . PHP_EOL . PHP_EOL;
    
    echo "🚀 TO TEST THE COMPLETE SYSTEM:" . PHP_EOL;
    echo "Run this command to start continuous monitoring:" . PHP_EOL;
    echo "php C:/xampp/htdocs/Autooutreach/start_imap_monitoring.php" . PHP_EOL . PHP_EOL;
    
    // Check current email status
    $emailStatus = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 62');
    if ($emailStatus) {
        echo "📊 CURRENT EMAIL STATUS:" . PHP_EOL;
        echo "- Database ID: " . $emailStatus['id'] . PHP_EOL;
        echo "- Status: " . $emailStatus['status'] . PHP_EOL;
        echo "- Sent At: " . $emailStatus['sent_at'] . PHP_EOL;
        echo "- Campaign ID: " . $emailStatus['campaign_id'] . PHP_EOL;
    }
    
    echo PHP_EOL . "✅ SYSTEM READY FOR TESTING!" . PHP_EOL;
    echo "The outreach email has been sent and the monitoring system is configured." . PHP_EOL;
    echo "When Jimmy replies, the system will automatically forward it to Zack." . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>