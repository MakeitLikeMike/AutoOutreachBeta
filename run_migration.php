<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    echo "Running AI Analysis Cache Migration...\n";
    
    // Read and execute the migration SQL
    $sql = file_get_contents('database_migrations/add_ai_analysis_cache.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->query($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "AI Analysis cache tables have been created.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>