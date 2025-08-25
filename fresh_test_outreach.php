<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== FRESH OUTREACH TEST - COMPLETE WORKFLOW ===" . PHP_EOL;

try {
    $db = new Database();
    $gmass = new GMassIntegration();
    
    // Test setup
    $jimmyEmail = 'jimmyrose1414@gmail.com';
    $campaignOwnerEmail = 'zackparker0905@gmail.com';
    $senderEmail = 'teamoutreach41@gmail.com';
    
    // Create new test campaign
    $campaignName = 'Fresh Test Campaign - ' . date('Y-m-d H:i');
    $db->execute('INSERT INTO campaigns (name, status, owner_email, created_at) VALUES (?, ?, ?, NOW())', 
                [$campaignName, 'active', $campaignOwnerEmail]);
    $campaignId = $db->lastInsertId();
    
    echo "✅ Created new campaign: $campaignName (ID: $campaignId)" . PHP_EOL;
    echo "Campaign Owner: $campaignOwnerEmail" . PHP_EOL . PHP_EOL;
    
    // Professional email content
    $subject = "Content Partnership Opportunity - Quality Guest Posts for Your Audience";
    
    $emailBody = "Hi Jimmy,<br><br>

I hope you're having a great day! I discovered your content while researching quality websites in the productivity and business space.<br><br>

<strong>WHY I'M REACHING OUT:</strong><br>
We specialize in creating high-quality guest content for established bloggers and would love to explore a partnership with you.<br><br>

<strong>WHAT WE OFFER:</strong><br>
• Original, well-researched articles (1,500+ words)<br>
• SEO-optimized content tailored to your audience<br>
• Professional writing with engaging storytelling<br>
• Relevant, authority backlinks<br>
• Social media promotion of published content<br><br>

<strong>SAMPLE TOPICS WE COULD CREATE:</strong><br>
• \"The Psychology Behind Productivity: Why Most Hacks Don't Work\"<br>
• \"Building Multiple Revenue Streams: A Data-Driven Approach\"<br>
• \"Remote Team Management: Lessons from 100+ Distributed Companies\"<br>
• \"AI in Small Business: Practical Applications That Actually Save Time\"<br><br>

<strong>WHAT WE'RE LOOKING FOR:</strong><br>
• One followed backlink within the article content<br>
• Author bio with website link<br>
• Social media mention when live<br><br>

I'd be happy to send writing samples and discuss how this could benefit your audience. We believe in creating genuine value rather than just link-building.<br><br>

Would you be open to exploring this partnership? I can share some recent work and discuss terms that work for both of us.<br><br>

Best regards,<br>
Sarah Martinez<br>
Content Strategy Manager<br>
Digital Content Solutions<br><br>

---<br>
<em>Campaign: $campaignName | Owner: $campaignOwnerEmail</em>";

    echo "📧 SENDING FRESH TEST OUTREACH:" . PHP_EOL;
    echo "To: $jimmyEmail" . PHP_EOL;
    echo "From: $senderEmail" . PHP_EOL;
    echo "Subject: $subject" . PHP_EOL;
    echo "Format: Clean HTML formatting" . PHP_EOL . PHP_EOL;
    
    // Store in database
    $db->execute('INSERT INTO outreach_emails (campaign_id, recipient_email, sender_email, subject, body, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$campaignId, $jimmyEmail, $senderEmail, $subject, $emailBody, 'ready']);
    
    $emailId = $db->lastInsertId();
    echo "Email stored in database with ID: $emailId" . PHP_EOL;
    
    // Send via GMass
    echo "Sending via GMass..." . PHP_EOL;
    $result = $gmass->sendEmail($senderEmail, $jimmyEmail, $subject, $emailBody);
    
    if ($result['success']) {
        $db->execute('UPDATE outreach_emails SET status = "sent", sent_at = NOW(), message_id = ? WHERE id = ?',
                    [$result['message_id'], $emailId]);
        
        echo "✅ SUCCESS: Fresh outreach email sent!" . PHP_EOL;
        echo "GMass Message ID: " . $result['message_id'] . PHP_EOL . PHP_EOL;
        
        echo "📋 TESTING INSTRUCTIONS:" . PHP_EOL;
        echo "1. ✅ Email sent to jimmyrose1414@gmail.com" . PHP_EOL;
        echo "2. 📨 Jimmy: Check your inbox for the new outreach email" . PHP_EOL;
        echo "3. 💬 Jimmy: Hit REPLY button and respond with interest" . PHP_EOL;
        echo "   Example: 'Hi Sarah, yes I'm interested! This sounds great.'" . PHP_EOL;
        echo "4. 🔍 System will monitor teamoutreach41@gmail.com for your reply" . PHP_EOL;
        echo "5. 📤 System will forward interested replies to zackparker0905@gmail.com" . PHP_EOL . PHP_EOL;
        
        echo "🔍 MONITORING SETUP:" . PHP_EOL;
        echo "Campaign ID: $campaignId" . PHP_EOL;
        echo "Email ID: $emailId" . PHP_EOL;
        echo "Monitoring: $senderEmail (for replies)" . PHP_EOL;
        echo "Forward Target: $campaignOwnerEmail" . PHP_EOL . PHP_EOL;
        
        echo "🚀 READY FOR TESTING!" . PHP_EOL;
        echo "When you reply, run: php monitor_fresh_test.php" . PHP_EOL;
        
    } else {
        echo "❌ FAILED to send email: " . $result['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>