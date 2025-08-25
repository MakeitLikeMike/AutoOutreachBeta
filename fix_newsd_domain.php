<?php
require_once 'config/database.php';
require_once 'classes/GMassIntegration.php';

try {
    $db = new Database();
    
    // Get domain and campaign info
    $domain = $db->fetchOne("SELECT * FROM target_domains WHERE domain = 'newsd.in' AND campaign_id = 9");
    $campaign = $db->fetchOne("SELECT * FROM campaigns WHERE id = 9");
    
    if (!$domain || !$campaign) {
        echo "Domain or campaign not found\n";
        exit;
    }
    
    echo "Creating manual outreach email...\n";
    
    // Create simple email content
    $subject = 'Partnership Opportunity';
    $body = 'Hi there,<br><br>';
    $body .= 'I hope this email finds you well. I am reaching out regarding a potential collaboration opportunity.<br><br>';
    $body .= 'I have been following the excellent work at ' . $domain['domain'] . ' and I am impressed with your content quality and audience engagement.<br><br>';
    $body .= 'I believe there could be a fantastic partnership opportunity between us. I would love to discuss featuring some content that I think would be valuable for your audience: https://www.highroller.com/en-ca/casino<br><br>';
    $body .= 'This could be a great fit for your platform, and I would be happy to discuss how we can make this mutually beneficial.<br><br>';
    $body .= 'Would you be interested in exploring this partnership? I would be glad to share more details and answer any questions you might have.<br><br>';
    $body .= 'Best regards,<br>Outreach Team';
    
    // Store in outreach_emails table
    $sql = "INSERT INTO outreach_emails (campaign_id, domain_id, subject, body, status, created_at) VALUES (?, ?, ?, ?, 'draft', NOW())";
    $db->execute($sql, [$campaign['id'], $domain['id'], $subject, $body]);
    $emailId = $db->lastInsertId();
    
    echo "Email created with ID: $emailId\n";
    
    // Update domain status
    $db->execute("UPDATE target_domains SET status = 'sending_email' WHERE id = ?", [$domain['id']]);
    echo "Domain status updated to sending_email\n";
    
    // Now try to send the email
    echo "Attempting to send email...\n";
    $gmass = new GMassIntegration();
    
    try {
        $result = $gmass->sendEmail('mike14delacruz@gmail.com', $domain['contact_email'], $subject, $body);
        
        // Update email status
        $db->execute("UPDATE outreach_emails SET status = 'sent', sent_at = NOW() WHERE id = ?", [$emailId]);
        $db->execute("UPDATE target_domains SET status = 'contacted' WHERE id = ?", [$domain['id']]);
        
        echo "Email sent successfully to " . $domain['contact_email'] . "\n";
        
    } catch (Exception $e) {
        $db->execute("UPDATE outreach_emails SET status = 'failed', error_message = ? WHERE id = ?", [$e->getMessage(), $emailId]);
        echo "Email sending failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>