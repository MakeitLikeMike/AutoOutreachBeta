<?php
/**
 * Manually trigger lead forwarding for positive replies
 */
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

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
    $gmass = new GMassIntegration();
    
    echo "🔍 Checking for positive replies to forward...\n\n";
    
    // Find positive replies that haven't been forwarded
    $positiveReplies = $db->fetchAll("
        SELECT 
            er.*,
            oe.subject as original_subject,
            oe.recipient_email,
            c.name as campaign_name,
            c.owner_email
        FROM email_replies er
        JOIN outreach_emails oe ON er.original_email_id = oe.id
        JOIN campaigns c ON er.campaign_id = c.id
        WHERE er.classification_category = 'positive'
        AND er.id NOT IN (SELECT reply_id FROM forwarded_leads WHERE reply_id IS NOT NULL)
        ORDER BY er.created_at DESC
    ");
    
    echo "Found " . count($positiveReplies) . " positive replies to forward\n\n";
    
    foreach ($positiveReplies as $reply) {
        echo "📧 Processing reply from: {$reply['sender_email']}\n";
        echo "Campaign: {$reply['campaign_name']}\n";
        echo "Reply: " . substr($reply['reply_content'], 0, 100) . "...\n";
        echo "Owner: {$reply['owner_email']}\n";
        
        // Create lead forwarding email
        $subject = "New Qualified Lead: {$reply['sender_email']} - {$reply['campaign_name']}";
        
        $body = "<h2>New Qualified Lead Received</h2><br>";
        $body .= "<strong>Campaign:</strong> {$reply['campaign_name']}<br>";
        $body .= "<strong>Lead Email:</strong> <a href=\"mailto:{$reply['sender_email']}?subject=Re: {$reply['reply_subject']}\">{$reply['sender_email']}</a><br>";
        $body .= "<strong>Reply Subject:</strong> {$reply['reply_subject']}<br>";
        $body .= "<strong>Classification:</strong> Positive Interest<br>";
        $body .= "<strong>Date:</strong> {$reply['reply_date']}<br><br>";
        
        // Clean the reply content before displaying
        $cleanReplyContent = extractReplyContent($reply['reply_content']);
        
        $body .= "<h3>Their Reply:</h3>";
        $body .= "<div style='background: #f5f5f5; padding: 15px; border-left: 4px solid #10b981;'>";
        $body .= nl2br(htmlspecialchars($cleanReplyContent));
        $body .= "</div><br>";
        
        $body .= "<h3>Original Outreach:</h3>";
        $body .= "<strong>Subject:</strong> {$reply['original_subject']}<br><br>";
        
        $body .= "<small>This lead was automatically detected and forwarded by your Outreach Automation system.</small>";
        
        try {
            // Send lead notification
            $result = $gmass->sendEmail(
                $reply['owner_email'],      // From campaign owner
                $reply['owner_email'],      // To campaign owner
                $subject,
                $body,
                ['reply_to' => $reply['sender_email']]  // Reply-To: Lead's actual email
            );
            
            if ($result['success']) {
                echo "✅ Lead forwarded successfully!\n";
                
                // Record the forwarding
                $insertSql = "INSERT INTO forwarded_leads (
                    reply_id, forwarded_to, subject, body, status, created_at
                ) VALUES (?, ?, ?, ?, 'sent', NOW())";
                
                $db->execute($insertSql, [
                    $reply['id'],
                    $reply['owner_email'],
                    $subject,
                    $body
                ]);
                
                $leadId = $db->lastInsertId();
                echo "📊 Lead recorded with ID: $leadId\n";
                
            } else {
                echo "❌ Failed to send lead notification\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error forwarding lead: " . $e->getMessage() . "\n";
        }
        
        echo "---\n";
    }
    
    echo "\n🎯 Lead forwarding completed!\n";
    echo "Check your email at the campaign owner addresses for new lead notifications.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>