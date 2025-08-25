<?php
require_once 'config/database.php';

echo "=== CHECKING FOR JIMMY'S REPLY ===" . PHP_EOL;

try {
    $db = new Database();
    
    // Check the test email status
    $testEmail = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 63');
    if ($testEmail) {
        echo "📧 Test Email Status:" . PHP_EOL;
        echo "- ID: " . $testEmail['id'] . PHP_EOL;
        echo "- To: " . $testEmail['recipient_email'] . PHP_EOL;
        echo "- From: " . $testEmail['sender_email'] . PHP_EOL;
        echo "- Subject: " . $testEmail['subject'] . PHP_EOL;
        echo "- Status: " . $testEmail['status'] . PHP_EOL;
        echo "- Sent At: " . $testEmail['sent_at'] . PHP_EOL;
        echo "- Campaign ID: " . $testEmail['campaign_id'] . PHP_EOL . PHP_EOL;
    }
    
    // Check for any replies to this email
    $replies = $db->fetchAll('SELECT * FROM email_replies WHERE original_email_id = 63 ORDER BY created_at DESC');
    
    if (count($replies) > 0) {
        echo "📬 REPLIES FOUND (" . count($replies) . "):" . PHP_EOL;
        foreach ($replies as $reply) {
            echo "- Reply ID: " . $reply['id'] . PHP_EOL;
            echo "- From: " . $reply['sender_email'] . PHP_EOL;
            echo "- Classification: " . ($reply['classification'] ?? 'pending') . PHP_EOL;
            echo "- Confidence: " . ($reply['classification_confidence'] ? ($reply['classification_confidence'] * 100) . '%' : 'N/A') . PHP_EOL;
            echo "- Date: " . $reply['reply_date'] . PHP_EOL;
            echo "- Content Preview: " . substr($reply['reply_content'] ?? '', 0, 100) . "..." . PHP_EOL . PHP_EOL;
        }
    } else {
        echo "📬 No replies received yet." . PHP_EOL;
        echo "Waiting for Jimmy to reply to jimmyrose1414@gmail.com..." . PHP_EOL . PHP_EOL;
    }
    
    // Check campaign owner
    $campaign = $db->fetchOne('SELECT * FROM campaigns WHERE id = 18');
    if ($campaign) {
        echo "👤 Campaign Owner: " . $campaign['owner_email'] . PHP_EOL;
        echo "📊 Leads Forwarded: " . ($campaign['leads_forwarded'] ?? 0) . PHP_EOL . PHP_EOL;
    }
    
    echo "🔍 MONITORING STATUS:" . PHP_EOL;
    echo "✅ Email sent successfully" . PHP_EOL;
    echo "⏳ Waiting for reply from jimmyrose1414@gmail.com" . PHP_EOL;
    echo "🎯 Will forward interested replies to zackparker0905@gmail.com" . PHP_EOL . PHP_EOL;
    
    echo "📝 INSTRUCTIONS:" . PHP_EOL;
    echo "1. Jimmy, check your email at jimmyrose1414@gmail.com" . PHP_EOL;
    echo "2. Reply with: 'Yes, I'm interested in guest posting opportunities'" . PHP_EOL;
    echo "3. Run this script again to check if reply was processed" . PHP_EOL;
    echo "4. Check zackparker0905@gmail.com for forwarded lead" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>