<?php
/**
 * Add App Passwords to IMAP Accounts
 */
require_once 'config/database.php';

echo "Adding App Passwords to IMAP Accounts" . PHP_EOL;
echo "=====================================" . PHP_EOL;
echo PHP_EOL;

try {
    $db = new Database();
    
    // Account credentials provided by user
    $accounts = [
        'mikedelacruz@agileserviceph.com' => 'gamj vsfx qvce hfgq',
        'jimmyrose1414@gmail.com' => 'wmsi zyhw fckn nmdo',
        'zackparker0905@gmail.com' => 'fadx majs shla bkfy',
        'mike14delacruz@gmail.com' => 'mbdymhyeqwsnzerc'
    ];
    
    echo "Processing " . count($accounts) . " accounts..." . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($accounts as $email => $appPassword) {
        // Clean up the app password (remove spaces)
        $cleanPassword = str_replace(' ', '', $appPassword);
        
        echo "Processing: {$email}" . PHP_EOL;
        echo "Password length: " . strlen($cleanPassword) . " characters" . PHP_EOL;
        
        // Check if account exists
        $existing = $db->fetchOne("SELECT id, email_address FROM imap_sender_accounts WHERE email_address = ?", [$email]);
        
        if ($existing) {
            // Update existing account
            $sql = "UPDATE imap_sender_accounts 
                    SET app_password = ?, 
                        connection_status = 'untested', 
                        updated_at = NOW() 
                    WHERE email_address = ?";
            
            $result = $db->execute($sql, [$cleanPassword, $email]);
            
            if ($result) {
                echo "✅ Updated password for account ID {$existing['id']}" . PHP_EOL;
            } else {
                echo "❌ Failed to update {$email}" . PHP_EOL;
            }
        } else {
            echo "⚠️  Account {$email} not found in database - skipping" . PHP_EOL;
        }
        
        echo PHP_EOL;
    }
    
    // Show final status
    echo "=== FINAL STATUS ===" . PHP_EOL;
    $allAccounts = $db->fetchAll("SELECT id, email_address, connection_status, updated_at FROM imap_sender_accounts ORDER BY email_address");
    
    foreach ($allAccounts as $account) {
        $hasPassword = !empty($db->fetchOne("SELECT app_password FROM imap_sender_accounts WHERE id = ? AND app_password != ''", [$account['id']])['app_password'] ?? '');
        $passwordStatus = $hasPassword ? "✅ Has password" : "❌ No password";
        
        echo "• {$account['email_address']} - {$passwordStatus} - Status: {$account['connection_status']}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    echo "🎯 Ready to test connections!" . PHP_EOL;
    echo "Visit admin panel and use 'Test Connection' buttons to verify accounts work." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>