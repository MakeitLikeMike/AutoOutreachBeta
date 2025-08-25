<?php
// Fix for campaign ID AUTO_INCREMENT issue
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "Checking campaigns table structure...\n";
    
    // Check current table structure
    $result = $db->fetchAll("DESCRIBE campaigns");
    
    echo "Current campaigns table structure:\n";
    foreach ($result as $column) {
        echo "- {$column['Field']}: {$column['Type']} | Key: {$column['Key']} | Extra: {$column['Extra']}\n";
    }
    
    // Check if id column has AUTO_INCREMENT
    $idColumn = null;
    foreach ($result as $column) {
        if ($column['Field'] === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if ($idColumn) {
        echo "\nID column found: {$idColumn['Type']} | Extra: {$idColumn['Extra']}\n";
        
        if (strpos($idColumn['Extra'], 'auto_increment') === false) {
            echo "ID column is missing AUTO_INCREMENT. Fixing...\n";
            
            // Fix the AUTO_INCREMENT
            $sql = "ALTER TABLE campaigns MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
            $db->execute($sql);
            
            echo "✓ Fixed AUTO_INCREMENT for campaigns.id\n";
        } else {
            echo "✓ ID column already has AUTO_INCREMENT\n";
        }
    } else {
        echo "ERROR: No 'id' column found in campaigns table!\n";
    }
    
    // Also check what's the current AUTO_INCREMENT value
    $tableStatus = $db->fetchAll("SHOW TABLE STATUS LIKE 'campaigns'");
    if (!empty($tableStatus)) {
        $autoIncrement = $tableStatus[0]['Auto_increment'] ?? 'NULL';
        echo "Current AUTO_INCREMENT value: $autoIncrement\n";
        
        // If AUTO_INCREMENT is NULL or 0, set it to a proper value
        if ($autoIncrement === 'NULL' || $autoIncrement == 0) {
            // Get the highest current ID
            $maxId = $db->fetchColumn("SELECT MAX(id) FROM campaigns");
            $nextId = ($maxId ? $maxId + 1 : 1);
            
            echo "Setting AUTO_INCREMENT to $nextId\n";
            $db->execute("ALTER TABLE campaigns AUTO_INCREMENT = $nextId");
            echo "✓ Set AUTO_INCREMENT to $nextId\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>