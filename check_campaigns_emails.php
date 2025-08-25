<?php
require_once 'config/database.php';
$db = new Database();
$result = $db->fetchAll('DESCRIBE campaigns');
echo "=== CAMPAIGNS TABLE EMAIL COLUMNS ===" . PHP_EOL;
foreach ($result as $column) {
    if (strpos($column['Field'], 'email') !== false || strpos($column['Field'], 'sender') !== false) {
        echo $column['Field'] . ' - ' . $column['Type'] . PHP_EOL;
    }
}
?>