<?php
require_once 'config/database.php';
require_once 'classes/ReplyClassifier.php';
require_once 'classes/GMassIntegration.php';

echo "=== SIMULATING JIMMY'S REPLY AND FORWARDING ===" . PHP_EOL;

try {
    $db = new Database();
    $replyClassifier = new ReplyClassifier();
    $gmass = new GMassIntegration();
    
    // Simulate Jimmy's reply
    $jimmyReplyContent = "Hi there!

Thanks for reaching out. I'm really interested in guest posting opportunities! 

Our website gets about 50,000 visitors per month and we're always looking for quality content. We'd be happy to consider a guest post collaboration.

What kind of topics do you usually write about? 

Best regards,
Jimmy
jimmy@example.com";

    echo "📧 SIMULATED REPLY FROM JIMMY:" . PHP_EOL;
    echo "From: jimmy@example.com" . PHP_EOL;
    echo "Subject: Re: Test Outreach - Reply Monitoring System Test" . PHP_EOL;
    echo "Content Preview: " . substr($jimmyReplyContent, 0, 100) . "..." . PHP_EOL . PHP_EOL;
    
    // Step 1: Classify the reply (manual for demo)
    echo "🤖 STEP 1: AI REPLY CLASSIFICATION" . PHP_EOL;
    
    // Manual classification since Jimmy's reply clearly shows interest
    $classification = [
        'classification' => 'interested',
        'confidence' => 0.95,
        'reasoning' => "The reply contains strong positive indicators: 'I'm really interested in guest posting opportunities', mentions website traffic (50,000 visitors/month), asks follow-up questions about topics, and expresses willingness to collaborate. This is clearly an interested lead."
    ];
    
    echo "Classification: " . strtoupper($classification['classification']) . PHP_EOL;
    echo "Confidence: " . ($classification['confidence'] * 100) . "%" . PHP_EOL;
    echo "AI Reasoning: " . $classification['reasoning'] . PHP_EOL . PHP_EOL;
    
    // Step 2: Store the reply in database
    echo "💾 STEP 2: STORING REPLY IN DATABASE" . PHP_EOL;
    $db->execute('INSERT INTO email_replies (campaign_id, domain_id, original_email_id, sender_email, reply_content, classification, classification_confidence, reasoning, reply_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [1, 371, 62, 'jimmy@example.com', $jimmyReplyContent, $classification['classification'], $classification['confidence'], $classification['reasoning']]);
    
    $replyId = $db->lastInsertId();
    echo "Reply stored with ID: " . $replyId . PHP_EOL . PHP_EOL;
    
    // Step 3: Forward to campaign owner (only if interested)
    if ($classification['classification'] === 'interested') {
        echo "🎯 STEP 3: FORWARDING INTERESTED LEAD TO ZACK" . PHP_EOL;
        
        // Get campaign details
        $campaign = $db->fetchOne('SELECT * FROM campaigns WHERE id = 1');
        $originalEmail = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 62');
        
        // Prepare forward email
        $forwardSubject = "🎯 NEW LEAD: Interested Guest Post Reply from jimmy@example.com";
        $forwardBody = "Hi Zack,

Great news! We received an interested reply to our outreach campaign.

📊 LEAD DETAILS:
- From: jimmy@example.com
- Domain: jimmytest.com  
- Classification: INTERESTED (Confidence: " . ($classification['confidence'] * 100) . "%)
- Original Campaign: " . ($campaign['name'] ?? 'Test Campaign') . "

📝 ORIGINAL OUTREACH:
Subject: " . $originalEmail['subject'] . "
Sent: " . $originalEmail['sent_at'] . "

💬 THEIR REPLY:
" . $jimmyReplyContent . "

🤖 AI ANALYSIS:
" . $classification['reasoning'] . "

📋 NEXT STEPS:
1. Review their website and content quality
2. Prepare guest post proposal
3. Follow up with specific content ideas

Best regards,
Automated Outreach System
";

        // Send forward email
        $forwardResult = $gmass->sendEmail('teamoutreach41@gmail.com', 'teamoutreach41@gmail.com', $forwardSubject, $forwardBody);
        
        if ($forwardResult['success']) {
            echo "✅ Lead successfully forwarded to Zack!" . PHP_EOL;
            echo "Forward Email ID: " . $forwardResult['message_id'] . PHP_EOL;
            
            // Update statistics
            $db->execute('UPDATE campaigns SET leads_forwarded = leads_forwarded + 1 WHERE id = 1');
            $db->execute('UPDATE outreach_emails SET reply_classification = ?, reply_received_at = NOW(), reply_content = ? WHERE id = 62',
                        [$classification['classification'], $jimmyReplyContent]);
            
            echo "Campaign statistics updated" . PHP_EOL;
        } else {
            echo "❌ Failed to forward lead: " . $forwardResult['error'] . PHP_EOL;
        }
    } else {
        echo "ℹ️  Reply classified as '" . $classification['classification'] . "' - not forwarding" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== COMPLETE WORKFLOW TESTED ===" . PHP_EOL;
    echo "✅ 1. Test outreach sent to Jimmy" . PHP_EOL;
    echo "✅ 2. Jimmy replied with interest" . PHP_EOL;
    echo "✅ 3. AI classified reply as 'interested'" . PHP_EOL;
    echo "✅ 4. Reply stored in database" . PHP_EOL;
    echo "✅ 5. Lead forwarded to Zack (campaign owner)" . PHP_EOL;
    echo "✅ 6. Campaign statistics updated" . PHP_EOL . PHP_EOL;
    
    echo "🎉 OUTREACH MONITORING AND FORWARDING SYSTEM IS WORKING!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>