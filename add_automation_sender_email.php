<?php
require_once 'config/database.php';

echo "=== ADDING AUTOMATION SENDER EMAIL COLUMN TO CAMPAIGNS ===" . PHP_EOL;

try {
    $db = new Database();
    
    // Check if column already exists
    $columns = $db->fetchAll('DESCRIBE campaigns');
    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'automation_sender_email') {
            $columnExists = true;
            break;
        }
    }
    
    if (!$columnExists) {
        echo "Adding automation_sender_email column..." . PHP_EOL;
        $db->execute('ALTER TABLE campaigns ADD COLUMN automation_sender_email VARCHAR(255) DEFAULT "teamoutreach41@gmail.com" AFTER owner_email');
        echo "✅ Column added successfully" . PHP_EOL;
    } else {
        echo "ℹ️ Column already exists" . PHP_EOL;
    }
    
    // Set default value for existing campaigns
    echo "Setting default automation sender for existing campaigns..." . PHP_EOL;
    $db->execute('UPDATE campaigns SET automation_sender_email = "teamoutreach41@gmail.com" WHERE automation_sender_email IS NULL OR automation_sender_email = ""');
    
    echo "✅ Database migration completed" . PHP_EOL . PHP_EOL;
    
    echo "📊 UPDATED CAMPAIGNS TABLE STRUCTURE:" . PHP_EOL;
    $result = $db->fetchAll('DESCRIBE campaigns');
    foreach ($result as $column) {
        if (strpos($column['Field'], 'email') !== false) {
            echo "- " . $column['Field'] . ' (' . $column['Type'] . ')' . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>