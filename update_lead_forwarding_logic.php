<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

echo "=== UPDATING LEAD FORWARDING WITH PROPER EMAIL SEPARATION ===" . PHP_EOL;

function createUpdatedForwardingEmail($replyDetails, $campaignDetails, $originalOutreach) {
    $forwardSubject = "NEW QUALIFIED LEAD: " . $replyDetails['sender_email'] . " - Ready for Your Personal Follow-up";
    
    $forwardBody = "Hi " . explode('@', $campaignDetails['owner_email'])[0] . ",<br><br>

🎯 <strong>EXCELLENT NEWS!</strong> We've received a qualified lead from our automated outreach campaign.<br><br>

<strong>LEAD SUMMARY:</strong><br>
📧 Contact: " . $replyDetails['sender_email'] . "<br>
🏆 Classification: " . strtoupper($replyDetails['classification']) . " (AI Confidence: " . ($replyDetails['confidence'] * 100) . "%)<br>
📅 Reply Date: " . $replyDetails['date'] . "<br>
📊 Campaign: " . $campaignDetails['name'] . "<br><br>

<strong>ORIGINAL OUTREACH (sent from automation):</strong><br>
From: " . $campaignDetails['automation_sender_email'] . "<br>
Subject: " . $originalOutreach['subject'] . "<br>
Sent: " . $originalOutreach['sent_at'] . "<br><br>

<strong>THEIR INTERESTED REPLY:</strong><br>
<em>" . nl2br(htmlspecialchars($replyDetails['content'])) . "</em><br><br>

<strong>AI ANALYSIS:</strong><br>
" . $replyDetails['reasoning'] . "<br><br>

<div style='background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin: 1rem 0;'>
<strong>🎯 NEXT STEPS - IMPORTANT:</strong><br><br>

<strong>Phase 1: COMPLETED ✅</strong><br>
✅ Automated outreach sent from " . $campaignDetails['automation_sender_email'] . "<br>
✅ Lead qualified and forwarded to you<br><br>

<strong>Phase 2: YOUR PERSONAL FOLLOW-UP</strong><br>
📧 <strong>Reply FROM:</strong> " . $campaignDetails['owner_email'] . " (your personal business email)<br>
📧 <strong>Reply TO:</strong> " . $replyDetails['sender_email'] . "<br><br>

<strong>Recommended Response:</strong><br>
<em>\"Hi [Name], Thank you for your interest in our content collaboration! I'm [Your Name], and I handle our partnership program personally. I'd love to discuss how we can create valuable content for your audience. Let me send you some writing samples and discuss terms that work for both of us. Best regards, [Your Name]\"</em>
</div>

<strong>WHY THE EMAIL SEPARATION MATTERS:</strong><br>
🤖 <strong>Automation Phase:</strong> " . $campaignDetails['automation_sender_email'] . " handles scale (hundreds of prospects)<br>
👤 <strong>Personal Phase:</strong> " . $campaignDetails['owner_email'] . " handles qualified leads (direct business)<br><br>

<strong>RECOMMENDED ACTION ITEMS:</strong><br>
1. Reply personally from " . $campaignDetails['owner_email'] . " within 24 hours<br>
2. Send writing samples as requested<br>
3. Discuss collaboration terms and content calendar<br>
4. Move to your personal CRM/email system for ongoing relationship<br><br>

This lead is ready for your personal touch!<br><br>

Best regards,<br>
Automated Lead Generation System<br>
<em>Campaign ID: " . $campaignDetails['id'] . " | Reply ID: " . $replyDetails['id'] . "</em>";

    return ['subject' => $forwardSubject, 'body' => $forwardBody];
}

echo "✅ Lead forwarding logic updated with proper email separation instructions" . PHP_EOL;
echo "📧 Forward emails now clearly explain the two-phase system:" . PHP_EOL;
echo "   Phase 1: Automation (" . $campaignDetails['automation_sender_email'] . " for scale)" . PHP_EOL;
echo "   Phase 2: Personal (" . $campaignDetails['owner_email'] . " for qualified leads)" . PHP_EOL;

// Test the new forwarding format
$testReplyDetails = [
    'id' => 999,
    'sender_email' => 'prospect@example.com',
    'classification' => 'interested',
    'confidence' => 0.95,
    'date' => date('Y-m-d H:i:s'),
    'content' => 'Hi, yes I\'m definitely interested in guest posting opportunities! Please send me some samples.',
    'reasoning' => 'Strong positive indicators showing clear interest in collaboration.'
];

$testCampaignDetails = [
    'id' => 999,
    'name' => 'Test Campaign',
    'owner_email' => 'zackparker0905@gmail.com',
    'automation_sender_email' => 'teamoutreach41@gmail.com'
];

$testOriginalOutreach = [
    'subject' => 'Guest Post Partnership Opportunity',
    'sent_at' => date('Y-m-d H:i:s')
];

$testForwardEmail = createUpdatedForwardingEmail($testReplyDetails, $testCampaignDetails, $testOriginalOutreach);

echo PHP_EOL . "📧 SAMPLE UPDATED FORWARD EMAIL:" . PHP_EOL;
echo "Subject: " . $testForwardEmail['subject'] . PHP_EOL . PHP_EOL;
echo "First 500 characters of body:" . PHP_EOL;
echo substr(strip_tags($testForwardEmail['body']), 0, 500) . "..." . PHP_EOL;

?>