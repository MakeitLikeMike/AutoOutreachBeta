<?php
require_once 'config/database.php';
$db = new Database();

try {
    echo "=== EMAIL_REPLIES TABLE STRUCTURE ===" . PHP_EOL;
    $result = $db->fetchAll('DESCRIBE email_replies');
    foreach ($result as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "email_replies table may not exist: " . $e->getMessage() . PHP_EOL;
    echo PHP_EOL . "=== CHECKING OUTREACH_EMAILS REPLY FIELDS ===" . PHP_EOL;
    $result2 = $db->fetchAll('DESCRIBE outreach_emails');
    foreach ($result2 as $column) {
        if (strpos($column['Field'], 'reply') !== false || strpos($column['Field'], 'classification') !== false) {
            echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
        }
    }
}
?>