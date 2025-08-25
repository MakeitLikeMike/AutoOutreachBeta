<?php
require_once 'config/database.php';

$db = new Database();

// Add GMass SMTP password
$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_smtp_password', '870ac706-5d9e-4517-9801-0cf0320ba9e7']
);

// Add default sender email
$db->execute(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', 
    ['gmass_default_sender', 'teamoutreach41@gmail.com']
);

echo 'GMass SMTP settings configured successfully!' . PHP_EOL;
echo 'SMTP Host: smtp.gmass.co' . PHP_EOL;
echo 'SMTP Username: gmass' . PHP_EOL;
echo 'SMTP Password: [configured]' . PHP_EOL;
echo 'Default Sender: teamoutreach41@gmail.com' . PHP_EOL;
?>