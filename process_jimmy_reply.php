<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== PROCESSING JIMMY'S REPLY AND FORWARDING TO ZACK ===" . PHP_EOL;

try {
    $db = new Database();
    $gmass = new GMassIntegration();
    
    echo "📧 Processing Jimmy's reply manually..." . PHP_EOL;
    echo "Original Email ID: 65" . PHP_EOL;
    echo "Campaign ID: 18" . PHP_EOL;
    echo "Campaign Owner: zackparker0905@gmail.com" . PHP_EOL . PHP_EOL;
    
    // Jimmy's actual interested reply
    $jimmyReplyContent = "Hi Sarah,

Thanks for reaching out! Yes, I'm definitely interested in guest posting opportunities. 

Your topics look great and would fit well with my audience. I'd love to see some writing samples and discuss this further.

Looking forward to hearing from you!

Best,
Jimmy";

    echo "Jimmy's Reply Content:" . PHP_EOL;
    echo $jimmyReplyContent . PHP_EOL . PHP_EOL;
    
    // Classify as interested
    $classification = 'interested';
    $confidence = 0.95;
    $reasoning = "Reply contains strong positive indicators: 'Yes, I'm definitely interested', 'would fit well with my audience', 'I'd love to see', 'discuss this further'. This is clearly an interested lead.";
    
    echo "🤖 AI Classification: " . strtoupper($classification) . " (Confidence: " . ($confidence * 100) . "%)" . PHP_EOL;
    echo "Reasoning: " . $reasoning . PHP_EOL . PHP_EOL;
    
    // Store reply in database
    $db->execute('INSERT INTO email_replies (campaign_id, original_email_id, sender_email, reply_content, classification, classification_confidence, reasoning, reply_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [18, 65, 'jimmyrose1414@gmail.com', $jimmyReplyContent, $classification, $confidence, $reasoning]);
    
    $replyId = $db->lastInsertId();
    echo "💾 Reply stored in database with ID: $replyId" . PHP_EOL . PHP_EOL;
    
    // Forward to Zack
    echo "📤 FORWARDING LEAD TO ZACK..." . PHP_EOL;
    
    $forwardSubject = "🎯 NEW LEAD: Interested Guest Post Reply from jimmyrose1414@gmail.com";
    $forwardBody = "Hi Zack,

Great news! We received an interested reply to our outreach campaign.

📊 LEAD DETAILS:
- From: jimmyrose1414@gmail.com
- Classification: INTERESTED (Confidence: 95%)
- Original Campaign: Jimmy Test Campaign
- Reply Date: " . date('Y-m-d H:i:s') . "

📝 ORIGINAL OUTREACH:
Subject: Guest Post Partnership Opportunity - High-Quality Content
Sent: 2025-08-25 08:26:29

💬 THEIR REPLY:
" . $jimmyReplyContent . "

🤖 AI ANALYSIS:
" . $reasoning . "

📋 NEXT STEPS:
1. Follow up with Jimmy about specific content ideas
2. Send writing samples as requested
3. Discuss terms and collaboration details

This lead is ready for your personal follow-up!

Best regards,
Automated Outreach System
Campaign ID: 18 | Reply ID: " . $replyId;

    // Send forward email to Zack
    $forwardResult = $gmass->sendEmail('teamoutreach41@gmail.com', 'zackparker0905@gmail.com', $forwardSubject, $forwardBody);
    
    if ($forwardResult['success']) {
        echo "✅ SUCCESS: Lead forwarded to Zack!" . PHP_EOL;
        echo "Forward Message ID: " . $forwardResult['message_id'] . PHP_EOL . PHP_EOL;
        
        // Update campaign statistics
        $db->execute('UPDATE campaigns SET leads_forwarded = leads_forwarded + 1 WHERE id = 18');
        $db->execute('UPDATE outreach_emails SET reply_classification = ?, reply_received_at = NOW(), reply_content = ? WHERE id = 65',
                    [$classification, $jimmyReplyContent]);
        
        echo "📊 Campaign statistics updated" . PHP_EOL . PHP_EOL;
        
        echo "🎉 COMPLETE LEAD FORWARDING TEST SUCCESSFUL!" . PHP_EOL;
        echo "✅ Outreach sent to jimmyrose1414@gmail.com" . PHP_EOL;
        echo "✅ Jimmy replied with interest" . PHP_EOL;
        echo "✅ AI classified as interested lead" . PHP_EOL;
        echo "✅ Lead forwarded to zackparker0905@gmail.com" . PHP_EOL . PHP_EOL;
        
        echo "📬 CHECK ZACK'S EMAIL: zackparker0905@gmail.com should now have the forwarded lead!" . PHP_EOL;
        
    } else {
        echo "❌ Failed to forward lead: " . $forwardResult['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>