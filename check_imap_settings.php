<?php
require_once 'config/database.php';

$db = new Database();
$imapSettings = $db->fetchAll('SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE "%imap%"');

echo "=== IMAP SETTINGS ===" . PHP_EOL;
foreach ($imapSettings as $setting) {
    if (strpos($setting['setting_key'], 'password') !== false) {
        echo $setting['setting_key'] . ': [HIDDEN]' . PHP_EOL;
    } else {
        echo $setting['setting_key'] . ': ' . $setting['setting_value'] . PHP_EOL;
    }
}

echo PHP_EOL . "=== ALL EMAIL RELATED SETTINGS ===" . PHP_EOL;
$emailSettings = $db->fetchAll('SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE "%email%" OR setting_key LIKE "%gmail%" OR setting_key LIKE "%gmass%"');
foreach ($emailSettings as $setting) {
    if (strpos($setting['setting_key'], 'password') !== false || strpos($setting['setting_key'], 'api_key') !== false) {
        $value = empty($setting['setting_value']) ? '[EMPTY]' : '[CONFIGURED]';
    } else {
        $value = $setting['setting_value'];
    }
    echo $setting['setting_key'] . ': ' . $value . PHP_EOL;
}
?>