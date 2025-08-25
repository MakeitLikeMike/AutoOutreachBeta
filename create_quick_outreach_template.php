<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

try {
    $db = new Database();
    $gmass = new GMassIntegration();
    
    echo "=== CREATING PROPER QUICK OUTREACH TEMPLATE ===" . PHP_EOL;
    
    // Create a proper Quick Outreach template
    $subject = "Quick Guest Post Opportunity - {DOMAIN}";
    $body = "Hi there,

Hope you're well! I found {DOMAIN} and love the content.

Would you be interested in a high-quality guest post? I can provide:
• Original, well-researched content
• Topics that fit your audience  
• Professional writing

Let me know if you'd like to see some samples!

Best,
Mike";
    
    $db->execute("INSERT INTO email_templates (name, subject, body, created_at) VALUES (?, ?, ?, NOW())", [
        'Quick Outreach Template',
        $subject,
        $body
    ]);
    
    $templateId = $db->lastInsertId();
    
    echo "✅ Created Quick Outreach template with ID: {$templateId}" . PHP_EOL;
    echo "Subject: {$subject}" . PHP_EOL;
    echo "Body:" . PHP_EOL . $body . PHP_EOL;
    
    // Update our test campaigns to use this template
    echo PHP_EOL . "Updating campaigns to use Quick Outreach template..." . PHP_EOL;
    $db->execute("UPDATE campaigns SET email_template_id = ? WHERE id IN (10, 11, 12)", [$templateId]);
    echo "✅ Updated campaigns 10, 11, 12 to use Quick Outreach template" . PHP_EOL;
    
    // Send corrected emails with Quick Outreach template
    echo PHP_EOL . "Sending corrected emails with Quick Outreach format..." . PHP_EOL;
    
    $domains = [
        'zackparkertest1.com',
        'zackparkertest2.com', 
        'zackblog.com',
        'mixedtest1.com',
        'mixedtest3.com'
    ];
    
    foreach ($domains as $domain) {
        $personalizedSubject = str_replace('{DOMAIN}', $domain, $subject);
        $personalizedBody = str_replace('{DOMAIN}', $domain, $body);
        
        $result = $gmass->sendEmail(
            'mike14delacruz@gmail.com',
            'zackparker0905@gmail.com',
            $personalizedSubject,
            $personalizedBody
        );
        
        if ($result['success']) {
            echo "✅ Sent Quick Outreach email for {$domain}" . PHP_EOL;
        } else {
            echo "❌ Failed to send for {$domain}" . PHP_EOL;
        }
        
        usleep(500000); // 0.5 second delay
    }
    
    echo PHP_EOL . "🎯 QUICK OUTREACH FORMAT EMAILS SENT!" . PHP_EOL;
    echo "Check zackparker0905@gmail.com for the new short format emails." . PHP_EOL;
    echo "Reply to test the clean lead forwarding system!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>