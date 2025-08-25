<?php
require_once 'config/database.php';
try {
    $db = new Database();
    echo "=== TARGET_DOMAINS TABLE STRUCTURE ===" . PHP_EOL;
    $result = $db->fetchAll('DESCRIBE target_domains');
    foreach ($result as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
    }
    
    echo PHP_EOL . "=== CAMPAIGNS TABLE STRUCTURE ===" . PHP_EOL;
    $result2 = $db->fetchAll('DESCRIBE campaigns');
    foreach ($result2 as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
    }
    
    echo PHP_EOL . "=== OUTREACH_EMAILS TABLE STRUCTURE ===" . PHP_EOL;
    $result3 = $db->fetchAll('DESCRIBE outreach_emails');
    foreach ($result3 as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>