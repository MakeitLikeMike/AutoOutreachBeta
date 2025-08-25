<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== MONITORING FOR FRESH TEST REPLY ===" . PHP_EOL;

try {
    $db = new Database();
    
    // Check the fresh test email (ID 66)
    $testEmail = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 66');
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
    
    // Check for replies to email ID 66
    $replies = $db->fetchAll('SELECT * FROM email_replies WHERE original_email_id = 66 ORDER BY created_at DESC');
    
    if (count($replies) > 0) {
        echo "📬 REPLIES FOUND (" . count($replies) . "):" . PHP_EOL;
        foreach ($replies as $reply) {
            echo "- Reply ID: " . $reply['id'] . PHP_EOL;
            echo "- From: " . $reply['sender_email'] . PHP_EOL;
            echo "- Classification: " . ($reply['classification'] ?? 'pending') . PHP_EOL;
            echo "- Confidence: " . ($reply['classification_confidence'] ? ($reply['classification_confidence'] * 100) . '%' : 'N/A') . PHP_EOL;
            echo "- Date: " . $reply['reply_date'] . PHP_EOL;
            echo "- Content Preview: " . substr($reply['reply_content'] ?? '', 0, 150) . "..." . PHP_EOL . PHP_EOL;
        }
        
        echo "📤 Processing most recent reply for forwarding..." . PHP_EOL;
        $latestReply = $replies[0];
        
        if ($latestReply['classification'] === 'interested') {
            echo "🎯 INTERESTED REPLY DETECTED - FORWARDING TO ZACK..." . PHP_EOL;
            
            // Forward to Zack with clean formatting
            $gmass = new GMassIntegration();
            
            $forwardSubject = "NEW LEAD: Interested Reply from " . $latestReply['sender_email'];
            
            $forwardBody = "Hi Zack,<br><br>

Great news! We received an interested reply to our outreach campaign.<br><br>

<strong>LEAD DETAILS:</strong><br>
- From: " . $latestReply['sender_email'] . "<br>
- Classification: " . strtoupper($latestReply['classification']) . " (Confidence: " . ($latestReply['classification_confidence'] * 100) . "%)<br>
- Reply Date: " . $latestReply['reply_date'] . "<br>
- Campaign: Fresh Test Campaign<br><br>

<strong>ORIGINAL OUTREACH:</strong><br>
Subject: " . $testEmail['subject'] . "<br>
Sent: " . $testEmail['sent_at'] . "<br><br>

<strong>THEIR REPLY:</strong><br>
<em>" . nl2br(htmlspecialchars($latestReply['reply_content'])) . "</em><br><br>

<strong>AI ANALYSIS:</strong><br>
" . $latestReply['reasoning'] . "<br><br>

<strong>RECOMMENDED NEXT STEPS:</strong><br>
1. Follow up with specific content samples<br>
2. Discuss collaboration terms<br>
3. Propose initial topic ideas<br><br>

This lead is ready for your personal follow-up!<br><br>

Best regards,<br>
Automated Outreach System<br>
Campaign ID: " . $testEmail['campaign_id'] . " | Reply ID: " . $latestReply['id'];

            $forwardResult = $gmass->sendEmail('teamoutreach41@gmail.com', 'zackparker0905@gmail.com', $forwardSubject, $forwardBody);
            
            if ($forwardResult['success']) {
                echo "✅ SUCCESS: Lead forwarded to Zack!" . PHP_EOL;
                echo "Forward Message ID: " . $forwardResult['message_id'] . PHP_EOL;
                
                // Update campaign stats
                $db->execute('UPDATE campaigns SET leads_forwarded = leads_forwarded + 1 WHERE id = ?', [$testEmail['campaign_id']]);
                
                echo "Campaign statistics updated" . PHP_EOL . PHP_EOL;
                echo "📬 ZACK SHOULD NOW HAVE THE PROPERLY FORMATTED LEAD!" . PHP_EOL;
            } else {
                echo "❌ Failed to forward: " . $forwardResult['error'] . PHP_EOL;
            }
        } else {
            echo "ℹ️ Reply classified as '" . $latestReply['classification'] . "' - not forwarding" . PHP_EOL;
        }
        
    } else {
        echo "📬 No replies found yet for Email ID 66." . PHP_EOL . PHP_EOL;
        
        echo "⏳ WAITING FOR JIMMY'S REPLY..." . PHP_EOL;
        echo "📝 Instructions:" . PHP_EOL;
        echo "1. Jimmy: Check jimmyrose1414@gmail.com inbox" . PHP_EOL;
        echo "2. Jimmy: Find the email 'Content Partnership Opportunity - Quality Guest Posts for Your Audience'" . PHP_EOL;
        echo "3. Jimmy: Hit REPLY and respond with interest" . PHP_EOL;
        echo "4. Run this script again after replying" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>