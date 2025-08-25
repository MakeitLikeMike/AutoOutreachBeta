<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== FIXING REPLY-TO HEADER FOR DIRECT REPLIES ===" . PHP_EOL;

try {
    $db = new Database();
    $gmass = new GMassIntegration();
    
    // Test the Reply-To functionality
    $testCampaign = $db->fetchOne('SELECT * FROM campaigns WHERE id = 19');
    
    echo "📧 CURRENT ISSUE:" . PHP_EOL;
    echo "- Outreach sent FROM: teamoutreach41@gmail.com" . PHP_EOL;
    echo "- Jimmy clicks Reply → goes TO: teamoutreach41@gmail.com ❌" . PHP_EOL;
    echo "- But we want replies to go TO: " . $testCampaign['owner_email'] . " ✅" . PHP_EOL . PHP_EOL;
    
    echo "🔧 SOLUTION: Add Reply-To header in outreach emails" . PHP_EOL;
    echo "- Email FROM: teamoutreach41@gmail.com (for sending/reputation)" . PHP_EOL;
    echo "- Reply-To: " . $testCampaign['owner_email'] . " (where replies go)" . PHP_EOL . PHP_EOL;
    
    // Test email with Reply-To header
    $testEmail = 'jimmyrose1414@gmail.com';
    $automationSender = 'teamoutreach41@gmail.com';
    $campaignOwner = $testCampaign['owner_email'];
    
    $subject = 'FIXED: Content Partnership - Replies Come to You Directly';
    
    $body = "Hi Jimmy,<br><br>

This is a test of our FIXED outreach system! Now when you click Reply, it will go directly to the campaign owner's personal business email.<br><br>

<strong>WHAT'S DIFFERENT:</strong><br>
• This email is sent FROM: $automationSender (automation system)<br>
• But replies go TO: $campaignOwner (campaign owner's personal email)<br>
• No more forwarding needed - direct communication!<br><br>

<strong>TEST INSTRUCTIONS:</strong><br>
1. Click REPLY to this email<br>
2. Your reply should automatically go to $campaignOwner<br>
3. No need to forward - you'll communicate directly!<br><br>

Please reply with 'Yes, this works!' to test the direct reply system.<br><br>

Best regards,<br>
Sarah Martinez<br>
Content Strategy Team<br><br>

---<br>
<em>Technical note: FROM=$automationSender, REPLY-TO=$campaignOwner</em>";

    echo "📤 SENDING TEST EMAIL WITH REPLY-TO HEADER:" . PHP_EOL;
    echo "FROM: $automationSender" . PHP_EOL;
    echo "TO: $testEmail" . PHP_EOL;
    echo "REPLY-TO: $campaignOwner" . PHP_EOL . PHP_EOL;
    
    // Send with Reply-To header
    $options = [
        'reply_to' => $campaignOwner
    ];
    
    $result = $gmass->sendEmail($automationSender, $testEmail, $subject, $body, $options);
    
    if ($result['success']) {
        echo "✅ TEST EMAIL SENT WITH REPLY-TO HEADER!" . PHP_EOL;
        echo "Message ID: " . $result['message_id'] . PHP_EOL . PHP_EOL;
        
        echo "🎯 NOW TEST THE REPLY:" . PHP_EOL;
        echo "1. Jimmy: Check jimmyrose1414@gmail.com inbox" . PHP_EOL;
        echo "2. Jimmy: Click REPLY button" . PHP_EOL;
        echo "3. Jimmy: Reply should automatically go TO: $campaignOwner" . PHP_EOL;
        echo "4. Zack: Check $campaignOwner for direct reply" . PHP_EOL . PHP_EOL;
        
        echo "📊 EXPECTED FLOW (FIXED):" . PHP_EOL;
        echo "✅ Outreach: $automationSender → $testEmail" . PHP_EOL;
        echo "✅ Reply: $testEmail → $campaignOwner (DIRECT!)" . PHP_EOL;
        echo "✅ Business: $campaignOwner ↔ $testEmail (personal communication)" . PHP_EOL . PHP_EOL;
        
        echo "🎉 NO MORE FORWARDING NEEDED!" . PHP_EOL;
        echo "Replies now go directly to campaign owner's personal email." . PHP_EOL;
        
    } else {
        echo "❌ Failed to send test email: " . $result['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>