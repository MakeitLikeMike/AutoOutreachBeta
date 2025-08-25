<?php
require_once 'config/database.php';

$db = new Database();

// Add your MultiSend account configuration
$senderAccounts = [
    'mikedelacruz@agileserviceph.com',
    'jimmyrose1414@gmail.com', 
    'zackparker0905@gmail.com'
];

// Update system settings
$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_sender_accounts', json_encode($senderAccounts)]
);

$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_multisend_enabled', '1']
);

$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_daily_limit_per_account', '75']
);

$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_current_sender_index', '0']
);

echo "✅ MultiSend Configuration Updated Successfully!" . PHP_EOL;
echo PHP_EOL;
echo "Configured Sender Accounts:" . PHP_EOL;
foreach ($senderAccounts as $index => $account) {
    echo ($index + 1) . ". " . $account . PHP_EOL;
}
echo PHP_EOL;
echo "Settings Updated:" . PHP_EOL;
echo "- MultiSend Enabled: Yes" . PHP_EOL;
echo "- Daily Limit Per Account: 75 emails" . PHP_EOL;
echo "- Total Daily Capacity: " . (count($senderAccounts) * 75) . " emails" . PHP_EOL;
echo "- Account Rotation: Enabled" . PHP_EOL;
?>