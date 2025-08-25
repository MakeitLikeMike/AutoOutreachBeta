<?php
/**
 * Manually check IMAP for replies to test monitoring
 */
require_once 'config/database.php';
require_once 'classes/IMAPMonitor.php';

try {
    echo "🔍 Manually checking IMAP for replies...\n\n";
    
    $db = new Database();
    $monitor = new IMAPMonitor();
    
    // Get IMAP accounts
    $accounts = $db->fetchAll("SELECT * FROM imap_sender_accounts WHERE connection_status = 'connected'");
    echo "Found " . count($accounts) . " IMAP accounts to check\n\n";
    
    foreach ($accounts as $account) {
        echo "📧 Checking account: " . $account['email_address'] . "\n";
        
        try {
            $result = $monitor->processAccount($account['id']);
            echo "✅ Processed " . $result['processed_count'] . " emails\n";
            echo "📬 Found " . $result['new_replies'] . " new replies\n";
            
            if ($result['new_replies'] > 0) {
                echo "🎉 New replies found! Checking details...\n";
                
                // Show recent replies
                $recentReplies = $db->fetchAll("
                    SELECT * FROM email_replies 
                    WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE) 
                    ORDER BY created_at DESC LIMIT 5
                ");
                
                foreach ($recentReplies as $reply) {
                    echo "- Reply from: " . $reply['from_email'] . "\n";
                    echo "  Subject: " . $reply['subject'] . "\n";
                    echo "  Classification: " . ($reply['classification_category'] ?? 'pending') . "\n";
                    echo "  Time: " . $reply['created_at'] . "\n\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Error processing account: " . $e->getMessage() . "\n";
        }
        
        echo "---\n";
    }
    
    // Also check if there are recent emails matching our test
    echo "\n🔍 Looking for emails matching our test subject...\n";
    $testEmails = $db->fetchAll("
        SELECT * FROM email_replies 
        WHERE subject LIKE '%Test Email Monitoring%' 
        OR subject LIKE '%Please Reply%'
        ORDER BY created_at DESC LIMIT 5
    ");
    
    if (empty($testEmails)) {
        echo "No replies found matching test subject\n";
        echo "This could mean:\n";
        echo "1. The reply hasn't been processed yet\n";
        echo "2. The subject line was changed in the reply\n";
        echo "3. IMAP monitoring needs to be running continuously\n";
    } else {
        echo "Found " . count($testEmails) . " matching replies!\n";
        foreach ($testEmails as $email) {
            echo "- " . $email['from_email'] . ": " . $email['subject'] . " (" . $email['created_at'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>