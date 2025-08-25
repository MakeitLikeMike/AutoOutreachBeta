<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Check if updated_at column exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM target_domains LIKE 'updated_at'");
    
    if (empty($columns)) {
        echo "Adding updated_at column to target_domains table..." . PHP_EOL;
        $db->execute('ALTER TABLE target_domains ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        echo 'Successfully added updated_at column to target_domains table' . PHP_EOL;
    } else {
        echo 'updated_at column already exists' . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage() . PHP_EOL;
}
?>