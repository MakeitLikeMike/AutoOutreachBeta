<?php
/**
 * Start IMAP monitoring manually to capture the reply
 */
require_once 'config/database.php';

function extractReplyContent($body) {
    // Decode quoted-printable if needed
    if (strpos($body, '=') !== false && preg_match('/=[0-9A-F]{2}/', $body)) {
        $body = quoted_printable_decode($body);
    }
    
    // Remove HTML tags
    $text = strip_tags($body);
    
    // Remove common email signatures and formatting headers
    $text = preg_replace('/^.*?From:.*?$/m', '', $text);
    $text = preg_replace('/^.*?Sent:.*?$/m', '', $text);
    $text = preg_replace('/^.*?To:.*?$/m', '', $text);
    $text = preg_replace('/^.*?Subject:.*?$/m', '', $text);
    $text = preg_replace('/^.*?Date:.*?$/m', '', $text);
    
    // Remove quoted text (lines starting with >)
    $lines = explode("\n", $text);
    $cleanLines = [];
    $foundReply = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines at the beginning
        if (!$foundReply && empty($line)) {
            continue;
        }
        
        // Stop at quoted lines (original email content)
        if (strpos($line, '>') === 0) {
            break;
        }
        
        // Stop at common reply separators
        if (preg_match('/^(On .* wrote:|From:|Sent:|Original Message|-----Original|_____Original)/', $line)) {
            break;
        }
        
        // Skip common email footers
        if (preg_match('/^(Sent from|Get Outlook|Sent via|Best regards|Kind regards|Sincerely|Thank you|Thanks)/', $line)) {
            break;
        }
        
        // Skip outlook/HTML formatting lines
        if (preg_match('/^(#outlook|body\{|\.ReadMsgBody|\.ExternalClass|\s*=\s*$)/', $line)) {
            continue;
        }
        
        // Skip lines that are just symbols or formatting
        if (preg_match('/^[\s\-=_]+$/', $line)) {
            continue;
        }
        
        $foundReply = true;
        $cleanLines[] = $line;
    }
    
    $result = implode("\n", $cleanLines);
    
    // Remove extra whitespace and line breaks
    $result = preg_replace('/\n\s*\n/', "\n", $result);
    $result = trim($result);
    
    return $result;
}

try {
    $db = new Database();
    
    echo "🚀 Starting manual IMAP monitoring...\n\n";
    
    // Add a monitoring job to the queue
    $sql = "INSERT INTO background_jobs (job_type, priority, status, created_at) VALUES ('monitor_replies', 'high', 'pending', NOW())";
    $db->execute($sql);
    $jobId = $db->lastInsertId();
    
    echo "✅ Added IMAP monitoring job (ID: $jobId)\n";
    
    // Process the job immediately
    echo "🔄 Processing IMAP monitoring job...\n";
    
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
    
    echo "Checking IMAP account: $email\n";
    
    // Connect to IMAP
    $connection = imap_open("{{$host}:{$port}/imap/ssl}INBOX", $email, $password);
    
    if (!$connection) {
        throw new Exception("IMAP connection failed: " . imap_last_error());
    }
    
    // Get recent emails (last 50 to be sure we catch the reply)
    $messageCount = imap_num_msg($connection);
    echo "Checking last 20 messages from $messageCount total...\n";
    
    $repliesFound = 0;
    
    for ($i = max(1, $messageCount - 19); $i <= $messageCount; $i++) {
        $header = imap_headerinfo($connection, $i);
        $subject = $header->subject ?? '';
        $fromEmail = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';
        $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : '';
        
        // Look for replies (RE: prefix or matching our test subject)
        if (stripos($subject, 'RE:') !== false || 
            stripos($subject, 'Test Email Monitoring') !== false ||
            $fromEmail === 'mike14delacruz@gmail.com') {
            
            echo "📧 Found potential reply: $subject from $fromEmail\n";
            
            // Get message body - try different parts for HTML/plain text
            $body = imap_fetchbody($connection, $i, 1);
            if (empty($body)) {
                $body = imap_fetchbody($connection, $i, 1.1);
            }
            if (empty($body)) {
                $body = imap_fetchbody($connection, $i, 1.2);
            }
            
            // Decode if base64 or quoted-printable
            $structure = imap_bodystruct($connection, $i, 1);
            if (isset($structure->encoding)) {
                if ($structure->encoding == 3) { // base64
                    $body = base64_decode($body);
                } elseif ($structure->encoding == 4) { // quoted-printable
                    $body = quoted_printable_decode($body);
                }
            }
            
            // Clean up body - extract actual reply content
            $bodyText = extractReplyContent($body);
            $bodyPreview = substr($bodyText, 0, 200);
            
            echo "Body preview: $bodyPreview...\n";
            
            // Check if this reply already exists
            $existing = $db->fetchOne("
                SELECT id FROM email_replies 
                WHERE sender_email = ? AND reply_date = ?
            ", [$fromEmail, $date]);
            
            if (!$existing) {
                // Find the original email this is replying to
                $originalEmail = $db->fetchOne("
                    SELECT id, campaign_id FROM outreach_emails 
                    WHERE recipient_email = ? 
                    ORDER BY sent_at DESC 
                    LIMIT 1
                ", [$fromEmail]);
                
                if ($originalEmail) {
                    echo "✅ Found original email ID: {$originalEmail['id']}, Campaign: {$originalEmail['campaign_id']}\n";
                    
                    // Insert the reply
                    $insertSql = "INSERT INTO email_replies (
                        original_email_id, campaign_id, sender_email, reply_subject, reply_content, 
                        reply_date, classification_category, processing_status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'positive', 'processed', NOW())";
                    
                    $db->execute($insertSql, [
                        $originalEmail['id'],
                        $originalEmail['campaign_id'],
                        $fromEmail,
                        $subject,
                        $bodyText,
                        $date
                    ]);
                    
                    $replyId = $db->lastInsertId();
                    echo "🎉 REPLY CAPTURED! ID: $replyId\n";
                    $repliesFound++;
                } else {
                    echo "⚠️ No matching original email found\n";
                }
            } else {
                echo "ℹ️ Reply already processed\n";
            }
            
            echo "---\n";
        }
    }
    
    imap_close($connection);
    
    // Update job status
    $db->execute("UPDATE background_jobs SET status = 'completed', updated_at = NOW() WHERE id = ?", [$jobId]);
    
    echo "\n✅ IMAP monitoring completed!\n";
    echo "📊 Results: $repliesFound new replies found\n";
    
    if ($repliesFound > 0) {
        echo "\n🎯 Success! Your reply has been captured by the monitoring system!\n";
        echo "You can now check the campaign analytics to see the reply data.\n";
    } else {
        echo "\n⚠️ No new replies found. This could mean:\n";
        echo "1. Your reply hasn't arrived yet\n";
        echo "2. The reply is in a different IMAP account\n";
        echo "3. The subject was changed significantly\n";
        echo "\nPlease verify you replied to: mike14delacruz@gmail.com\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>