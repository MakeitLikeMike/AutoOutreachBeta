<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';
require_once 'classes/ChatGPTIntegration.php';

try {
    $db = new Database();
    
    // Test domain for Jimmy
    $testDomain = 'jimmytest.com';
    $jimmyEmail = 'jimmy@jimmytest.com';
    
    echo "=== SENDING TEST OUTREACH TO JIMMY ===" . PHP_EOL;
    
    // Insert test domain - get campaign_id first
    $testCampaign = $db->fetchOne('SELECT id FROM campaigns WHERE status = "active" LIMIT 1');
    if (!$testCampaign) {
        // Create test campaign
        $db->execute('INSERT INTO campaigns (name, status, created_at) VALUES (?, ?, NOW())', ['Test Monitoring Campaign', 'active']);
        $testCampaignId = $db->lastInsertId();
    } else {
        $testCampaignId = $testCampaign['id'];
    }
    
    $db->execute('INSERT INTO target_domains (campaign_id, domain, status, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status = VALUES(status)', [$testCampaignId, $testDomain, 'approved']);
    $domainId = $db->lastInsertId();
    
    if (!$domainId) {
        // Get existing domain ID
        $domain = $db->fetchOne('SELECT id FROM target_domains WHERE domain = ?', [$testDomain]);
        $domainId = $domain['id'];
    }
    
    echo "Domain ID: " . $domainId . PHP_EOL;
    echo "Campaign ID: " . $testCampaignId . PHP_EOL;
    
    // Use the test campaign we already have
    $campaignId = $testCampaignId;
    
    // Generate AI content for Jimmy
    $chatgpt = new ChatGPTIntegration();
    $emailContent = $chatgpt->generateOutreachEmail($testDomain, [
        'business_type' => 'Technology Blog',
        'main_topics' => 'Web development, AI, automation',
        'target_audience' => 'Developers and tech professionals'
    ]);
    
    echo "Generated Subject: " . $emailContent['subject'] . PHP_EOL;
    
    // Create outreach email record
    $db->execute('INSERT INTO outreach_emails (campaign_id, domain_id, recipient_email, subject, content, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$campaignId, $domainId, $jimmyEmail, $emailContent['subject'], $emailContent['content'], 'ready']);
    
    $emailId = $db->lastInsertId();
    echo "Outreach Email ID: " . $emailId . PHP_EOL;
    
    // Send via GMass
    $gmass = new GMassIntegration();
    $result = $gmass->sendEmail($jimmyEmail, $emailContent['subject'], $emailContent['content']);
    
    if ($result['success']) {
        // Update email status
        $db->execute('UPDATE outreach_emails SET status = "sent", sent_at = NOW(), gmass_message_id = ? WHERE id = ?',
                    [$result['message_id'], $emailId]);
        
        echo PHP_EOL . "✅ SUCCESS: Test outreach sent to Jimmy!" . PHP_EOL;
        echo "Email ID: " . $emailId . PHP_EOL;
        echo "GMass Message ID: " . $result['message_id'] . PHP_EOL;
        echo "Subject: " . $emailContent['subject'] . PHP_EOL;
        echo "Campaign Owner: teamoutreach41@gmail.com" . PHP_EOL;
        echo PHP_EOL;
        echo "📧 NEXT STEP: Jimmy should reply to this email" . PHP_EOL;
        echo "🔄 The reply will be automatically forwarded to Zack (campaign owner)" . PHP_EOL;
    } else {
        echo PHP_EOL . "❌ FAILED: Could not send email - " . $result['error'] . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>