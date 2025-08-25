<?php
/**
 * Simple direct IMAP check for replies
 */
require_once 'config/database.php';

try {
    echo "🔍 Direct IMAP check for replies...\n\n";
    
    $db = new Database();
    
    // Get IMAP settings
    $settings = [];
    $settingResults = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'imap_%'");
    foreach ($settingResults as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    $host = $settings['imap_host'] ?? '';
    $email = $settings['imap_email'] ?? '';  
    $password = $settings['imap_password'] ?? '';
    $port = $settings['imap_port'] ?? '993';
    
    echo "Connecting to: $host:$port with $email\n";
    
    // Connect to IMAP
    $connection = imap_open("{{$host}:{$port}/imap/ssl}INBOX", $email, $password);
    
    if (!$connection) {
        throw new Exception("IMAP connection failed: " . imap_last_error());
    }
    
    echo "✅ Connected successfully!\n";
    
    // Get recent emails (last 5)
    $messageCount = imap_num_msg($connection);
    echo "Total messages: $messageCount\n\n";
    
    echo "📬 Checking last 10 messages for replies...\n";
    
    for ($i = max(1, $messageCount - 9); $i <= $messageCount; $i++) {
        $header = imap_headerinfo($connection, $i);
        $subject = $header->subject ?? '';
        $from = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';
        $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : '';
        
        echo "Message $i:\n";
        echo "  From: $from\n";
        echo "  Subject: $subject\n";
        echo "  Date: $date\n";
        
        // Check if this is a reply to our test
        if (stripos($subject, 'Test Email Monitoring') !== false || 
            stripos($subject, 'RE:') !== false ||
            stripos($subject, 'Please Reply') !== false) {
            
            echo "  🎯 POTENTIAL REPLY FOUND!\n";
            
            // Get message body
            $body = imap_fetchbody($connection, $i, 1);
            if (empty($body)) {
                $body = imap_fetchbody($connection, $i, 1.1);
            }
            
            echo "  Body preview: " . substr(strip_tags($body), 0, 100) . "...\n";
            
            // Check if already processed
            $existing = $db->fetchOne("SELECT id FROM email_replies WHERE subject LIKE ? AND from_email = ?", 
                                    ["%$subject%", $from]);
            
            if ($existing) {
                echo "  ✅ Already processed (Reply ID: {$existing['id']})\n";
            } else {
                echo "  ⚠️ NOT YET PROCESSED - This should be captured by monitoring!\n";
            }
        }
        
        echo "\n";
    }
    
    imap_close($connection);
    
    // Also check our outreach_emails table for the test email
    echo "🔍 Checking for our test email in database...\n";
    $testEmail = $db->fetchOne("SELECT * FROM outreach_emails WHERE subject LIKE '%Test Email Monitoring%' ORDER BY id DESC LIMIT 1");
    
    if ($testEmail) {
        echo "✅ Found test email in database:\n";
        echo "  ID: {$testEmail['id']}\n";
        echo "  Subject: {$testEmail['subject']}\n";
        echo "  Recipient: {$testEmail['recipient_email']}\n";
        echo "  Sent: {$testEmail['sent_at']}\n";
        
        // Check for replies to this email
        $replies = $db->fetchAll("SELECT * FROM email_replies WHERE original_email_id = ?", [$testEmail['id']]);
        echo "  Replies found: " . count($replies) . "\n";
        
        foreach ($replies as $reply) {
            echo "  - Reply from: {$reply['from_email']} at {$reply['created_at']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>