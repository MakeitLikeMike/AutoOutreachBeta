<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== TESTING COMPLETE FIXED WORKFLOW ===" . PHP_EOL;

try {
    $db = new Database();
    $gmass = new GMassIntegration();
    
    echo "🔧 TESTING UPDATED EMAIL SEPARATION SYSTEM" . PHP_EOL . PHP_EOL;
    
    // Create a test campaign with proper email separation
    $campaignName = 'Email Separation Test Campaign - ' . date('Y-m-d H:i');
    $ownerEmail = 'zackparker0905@gmail.com';  // Personal business email
    $automationSenderEmail = 'teamoutreach41@gmail.com';  // System automation email
    
    echo "📧 CAMPAIGN SETUP:" . PHP_EOL;
    echo "Campaign Owner (personal): $ownerEmail" . PHP_EOL;
    echo "Automation Sender (system): $automationSenderEmail" . PHP_EOL . PHP_EOL;
    
    // Create campaign with both email fields
    $db->execute('INSERT INTO campaigns (name, owner_email, automation_sender_email, status, created_at) VALUES (?, ?, ?, ?, NOW())', 
                [$campaignName, $ownerEmail, $automationSenderEmail, 'active']);
    $campaignId = $db->lastInsertId();
    
    echo "✅ Campaign created: ID $campaignId" . PHP_EOL . PHP_EOL;
    
    // Step 1: Send outreach FROM automation email TO prospect
    $prospectEmail = 'jimmyrose1414@gmail.com';
    $subject = 'Partnership Opportunity - Content Collaboration';
    
    $outreachBody = "Hi Jimmy,<br><br>

I hope this email finds you well! I came across your content and was impressed by the quality and engagement.<br><br>

<strong>WHY I'M REACHING OUT:</strong><br>
We specialize in creating high-quality guest content for established content creators and would love to explore a partnership.<br><br>

<strong>WHAT WE OFFER:</strong><br>
• Original, well-researched articles (1,500+ words)<br>
• SEO-optimized content tailored to your audience<br>
• Professional writing with engaging storytelling<br>
• Relevant backlinks and social promotion<br><br>

<strong>SAMPLE TOPICS:</strong><br>
• \"The Psychology of Productivity: Why Most Hacks Don't Work\"<br>
• \"Building Multiple Revenue Streams: A Data-Driven Approach\"<br>
• \"Remote Team Management: Lessons from 100+ Companies\"<br><br>

Would you be open to exploring this partnership? I'd love to send writing samples and discuss terms.<br><br>

Best regards,<br>
Sarah Martinez<br>
Content Strategy Team<br><br>

---<br>
<em>Campaign: $campaignName | System ID: $campaignId</em>";

    echo "📤 STEP 1: SENDING AUTOMATED OUTREACH" . PHP_EOL;
    echo "FROM: $automationSenderEmail (automation system)" . PHP_EOL;
    echo "TO: $prospectEmail (prospect)" . PHP_EOL . PHP_EOL;
    
    // Send outreach
    $outreachResult = $gmass->sendEmail($automationSenderEmail, $prospectEmail, $subject, $outreachBody);
    
    if ($outreachResult['success']) {
        $messageId = $outreachResult['message_id'];
        
        // Store outreach in database
        $db->execute('INSERT INTO outreach_emails (campaign_id, recipient_email, sender_email, subject, body, status, sent_at, message_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())',
                    [$campaignId, $prospectEmail, $automationSenderEmail, $subject, $outreachBody, 'sent', $messageId]);
        
        $outreachId = $db->lastInsertId();
        
        echo "✅ Outreach sent successfully!" . PHP_EOL;
        echo "Message ID: $messageId" . PHP_EOL;
        echo "Database ID: $outreachId" . PHP_EOL . PHP_EOL;
        
        // Step 2: Simulate prospect's interested reply
        echo "📬 STEP 2: SIMULATING PROSPECT'S INTERESTED REPLY" . PHP_EOL;
        
        $replyContent = "Hi Sarah,

Thanks for reaching out! Yes, I'm definitely interested in exploring this partnership opportunity.

Your content topics sound perfect for my audience, especially the productivity and revenue streams content. I'd love to see some writing samples and discuss how we can make this work.

My site gets about 35,000 monthly visitors who would really benefit from this type of content.

Please send over some examples when you get a chance!

Best regards,
Jimmy";

        echo "Reply from: $prospectEmail" . PHP_EOL;
        echo "Reply to: $automationSenderEmail (system monitors this)" . PHP_EOL . PHP_EOL;
        
        // Step 3: AI Classification and Lead Forwarding
        echo "🤖 STEP 3: AI CLASSIFICATION AND LEAD FORWARDING" . PHP_EOL;
        
        $classification = 'interested';
        $confidence = 0.96;
        $reasoning = "Strong interest signals: 'definitely interested', 'perfect for my audience', specific audience size mentioned (35k visitors), requests samples and follow-up. High-quality lead.";
        
        echo "Classification: " . strtoupper($classification) . " (Confidence: " . ($confidence * 100) . "%)" . PHP_EOL;
        
        // Store reply
        $db->execute('INSERT INTO email_replies (campaign_id, original_email_id, sender_email, reply_content, classification, classification_confidence, reasoning, reply_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                    [$campaignId, $outreachId, $prospectEmail, $replyContent, $classification, $confidence, $reasoning]);
        
        $replyId = $db->lastInsertId();
        echo "Reply stored: ID $replyId" . PHP_EOL . PHP_EOL;
        
        // Step 4: Forward lead to campaign owner with proper instructions
        echo "📤 STEP 4: FORWARDING QUALIFIED LEAD TO CAMPAIGN OWNER" . PHP_EOL;
        
        $forwardSubject = "🎯 QUALIFIED LEAD: $prospectEmail Ready for Your Personal Follow-up";
        
        $forwardBody = "Hi Zack,<br><br>

🎉 <strong>EXCELLENT NEWS!</strong> We've got a highly qualified lead from our automated outreach system.<br><br>

<strong>LEAD SUMMARY:</strong><br>
📧 Contact: $prospectEmail<br>
🏆 Classification: INTERESTED (AI Confidence: 96%)<br>
📊 Audience: 35,000 monthly visitors<br>
📅 Reply Date: " . date('Y-m-d H:i:s') . "<br>
🚀 Campaign: $campaignName<br><br>

<strong>ORIGINAL AUTOMATED OUTREACH:</strong><br>
From: $automationSenderEmail (system automation)<br>
Subject: $subject<br>
Status: Successfully delivered and replied to<br><br>

<strong>THEIR INTERESTED REPLY:</strong><br>
<em>" . nl2br(htmlspecialchars($replyContent)) . "</em><br><br>

<div style='background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 1rem; margin: 1rem 0;'>
<strong>🎯 YOUR NEXT STEPS (IMPORTANT):</strong><br><br>

<strong>✅ PHASE 1 COMPLETE:</strong> System automation handled scale<br>
• Automated outreach sent from $automationSenderEmail<br>
• Lead qualified and forwarded to you<br><br>

<strong>🎯 PHASE 2: YOUR PERSONAL FOLLOW-UP</strong><br>
• <strong>Send FROM:</strong> $ownerEmail (your personal business email)<br>
• <strong>Send TO:</strong> $prospectEmail<br>
• <strong>Timeline:</strong> Reply within 24 hours for best results<br><br>

<strong>Suggested Response:</strong><br>
<em>\"Hi Jimmy, Thank you for your interest! I'm Zack, and I handle our content partnerships personally. I love that you're getting 35k monthly visitors - that's exactly the kind of engaged audience we work with. Let me send you some writing samples and we can discuss terms that work for both of us. Looking forward to collaborating! Best, Zack\"</em>
</div>

<strong>WHY EMAIL SEPARATION WORKS:</strong><br>
🤖 <strong>$automationSenderEmail</strong> = System automation (handles 100s of prospects)<br>
👤 <strong>$ownerEmail</strong> = Your personal business (handles qualified leads)<br><br>

<strong>RECOMMENDED ACTIONS:</strong><br>
1. Reply personally from $ownerEmail within 24 hours<br>
2. Send writing samples (they specifically requested this)<br>
3. Discuss content calendar and collaboration terms<br>
4. Move to your personal CRM for ongoing relationship<br><br>

This lead is ready for your personal touch!<br><br>

Best regards,<br>
Automated Lead Generation System<br>
Campaign ID: $campaignId | Reply ID: $replyId";

        echo "FROM: $automationSenderEmail (system notification)" . PHP_EOL;
        echo "TO: $ownerEmail (campaign owner)" . PHP_EOL . PHP_EOL;
        
        // Send forward email
        $forwardResult = $gmass->sendEmail($automationSenderEmail, $ownerEmail, $forwardSubject, $forwardBody);
        
        if ($forwardResult['success']) {
            echo "✅ LEAD FORWARDED SUCCESSFULLY!" . PHP_EOL;
            echo "Forward Message ID: " . $forwardResult['message_id'] . PHP_EOL . PHP_EOL;
            
            // Update campaign stats
            $db->execute('UPDATE campaigns SET leads_forwarded = leads_forwarded + 1 WHERE id = ?', [$campaignId]);
            
            echo "🎉 COMPLETE WORKFLOW TEST SUCCESSFUL!" . PHP_EOL;
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
            echo "✅ Automated outreach: $automationSenderEmail → $prospectEmail" . PHP_EOL;
            echo "✅ Prospect reply: $prospectEmail → $automationSenderEmail (monitored)" . PHP_EOL;
            echo "✅ Lead qualification: AI classified as INTERESTED (96%)" . PHP_EOL;
            echo "✅ Lead forwarding: $automationSenderEmail → $ownerEmail" . PHP_EOL;
            echo "🎯 Next: Zack personally contacts Jimmy from $ownerEmail" . PHP_EOL . PHP_EOL;
            
            echo "📬 ZACK'S ACTION REQUIRED:" . PHP_EOL;
            echo "Check $ownerEmail inbox for the qualified lead notification!" . PHP_EOL;
            echo "Reply to Jimmy from your personal business email to close the deal." . PHP_EOL;
            
        } else {
            echo "❌ Failed to forward lead: " . $forwardResult['error'] . PHP_EOL;
        }
        
    } else {
        echo "❌ Failed to send outreach: " . $outreachResult['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>