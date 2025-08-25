<?php
require_once 'config/database.php';

echo "=== CHECKING LATEST EMAIL AND REPLIES ===" . PHP_EOL;

try {
    $db = new Database();
    
    // Check the latest email (ID 65)
    $latestEmail = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 65');
    if ($latestEmail) {
        echo "📧 Latest Email Status:" . PHP_EOL;
        echo "- ID: " . $latestEmail['id'] . PHP_EOL;
        echo "- To: " . $latestEmail['recipient_email'] . PHP_EOL;
        echo "- From: " . $latestEmail['sender_email'] . PHP_EOL;
        echo "- Subject: " . $latestEmail['subject'] . PHP_EOL;
        echo "- Status: " . $latestEmail['status'] . PHP_EOL;
        echo "- Sent At: " . $latestEmail['sent_at'] . PHP_EOL;
        echo "- Campaign ID: " . $latestEmail['campaign_id'] . PHP_EOL . PHP_EOL;
    }
    
    // Check for replies to the latest email
    $replies = $db->fetchAll('SELECT * FROM email_replies WHERE original_email_id = 65 ORDER BY created_at DESC');
    
    if (count($replies) > 0) {
        echo "📬 REPLIES FOUND (" . count($replies) . "):" . PHP_EOL;
        foreach ($replies as $reply) {
            echo "- Reply ID: " . $reply['id'] . PHP_EOL;
            echo "- From: " . $reply['sender_email'] . PHP_EOL;
            echo "- Classification: " . ($reply['classification'] ?? 'pending') . PHP_EOL;
            echo "- Date: " . $reply['reply_date'] . PHP_EOL;
            echo "- Content Preview: " . substr($reply['reply_content'] ?? '', 0, 200) . "..." . PHP_EOL . PHP_EOL;
        }
    } else {
        echo "📬 No replies found for email ID 65." . PHP_EOL . PHP_EOL;
    }
    
    // Check all recent replies
    echo "🔍 CHECKING ALL RECENT REPLIES:" . PHP_EOL;
    $allReplies = $db->fetchAll('SELECT * FROM email_replies WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY created_at DESC');
    
    if (count($allReplies) > 0) {
        echo "Found " . count($allReplies) . " recent replies:" . PHP_EOL;
        foreach ($allReplies as $reply) {
            echo "- Reply ID: " . $reply['id'] . " | Original Email: " . $reply['original_email_id'] . " | From: " . $reply['sender_email'] . " | Time: " . $reply['created_at'] . PHP_EOL;
        }
    } else {
        echo "No replies in the last hour." . PHP_EOL;
    }
    
    echo PHP_EOL . "💡 NEXT STEP: Need to run IMAP monitoring to detect Jimmy's reply" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>