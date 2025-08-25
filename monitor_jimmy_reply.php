<?php
require_once 'config/database.php';
require_once 'classes/IMAPMonitor.php';
require_once 'classes/ReplyClassifier.php';
require_once 'classes/LeadForwarder.php';

echo "=== MONITORING FOR JIMMY'S REPLY ===" . PHP_EOL;

try {
    $db = new Database();
    
    // Initialize monitoring classes
    $imapMonitor = new IMAPMonitor();
    $replyClassifier = new ReplyClassifier();
    $leadForwarder = new LeadForwarder();
    
    echo "Starting IMAP monitoring for replies..." . PHP_EOL;
    echo "Monitoring account: teamoutreach41@gmail.com" . PHP_EOL;
    echo "Looking for replies to outreach emails..." . PHP_EOL . PHP_EOL;
    
    // Check for our specific test email
    $testEmail = $db->fetchOne('SELECT * FROM outreach_emails WHERE id = 62');
    if ($testEmail) {
        echo "Test email found in database:" . PHP_EOL;
        echo "- ID: " . $testEmail['id'] . PHP_EOL;
        echo "- To: " . $testEmail['recipient_email'] . PHP_EOL;
        echo "- Subject: " . $testEmail['subject'] . PHP_EOL;
        echo "- Status: " . $testEmail['status'] . PHP_EOL . PHP_EOL;
    }
    
    // Run IMAP monitoring
    echo "Checking IMAP for new replies..." . PHP_EOL;
    
    // Get unread emails
    $newReplies = $imapMonitor->checkForReplies();
    
    if ($newReplies && count($newReplies) > 0) {
        echo "Found " . count($newReplies) . " new replies!" . PHP_EOL . PHP_EOL;
        
        foreach ($newReplies as $reply) {
            echo "Processing reply:" . PHP_EOL;
            echo "- From: " . $reply['from'] . PHP_EOL;
            echo "- Subject: " . $reply['subject'] . PHP_EOL;
            echo "- Date: " . $reply['date'] . PHP_EOL;
            echo "- Preview: " . substr($reply['body'], 0, 100) . "..." . PHP_EOL . PHP_EOL;
            
            // Classify the reply
            $classification = $replyClassifier->classifyReply($reply['body']);
            echo "Reply Classification: " . $classification['classification'] . PHP_EOL;
            echo "Confidence: " . ($classification['confidence'] * 100) . "%" . PHP_EOL;
            echo "Reasoning: " . $classification['reasoning'] . PHP_EOL . PHP_EOL;
            
            // If it's an interested reply, forward it
            if ($classification['classification'] === 'interested') {
                echo "🎯 INTERESTED LEAD DETECTED!" . PHP_EOL;
                echo "Forwarding to campaign owner..." . PHP_EOL;
                
                $forwardResult = $leadForwarder->forwardLead($reply, $testEmail['campaign_id']);
                
                if ($forwardResult['success']) {
                    echo "✅ Reply successfully forwarded to Zack!" . PHP_EOL;
                    echo "Forward Email ID: " . $forwardResult['forward_id'] . PHP_EOL;
                } else {
                    echo "❌ Failed to forward reply: " . $forwardResult['error'] . PHP_EOL;
                }
            }
        }
    } else {
        echo "No new replies found at this time." . PHP_EOL;
        echo "Waiting for Jimmy to reply..." . PHP_EOL;
        echo "(Jimmy should reply with: 'I'm interested in guest posting opportunities')" . PHP_EOL . PHP_EOL;
        
        echo "To manually check again, run: php monitor_jimmy_reply.php" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== MONITORING SUMMARY ===" . PHP_EOL;
    echo "- Test email sent: ✅" . PHP_EOL;
    echo "- IMAP monitoring: Active" . PHP_EOL;
    echo "- Reply classification: Ready" . PHP_EOL;
    echo "- Lead forwarding: Ready" . PHP_EOL;
    echo "- Waiting for: Jimmy's reply" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>