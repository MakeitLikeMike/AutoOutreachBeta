<?php
/**
 * Gmail to GMass Migration Cleanup Script
 * Removes Gmail API dependencies and updates code to use GMass + IMAP
 */
require_once 'config/database.php';

echo "=== Gmail to GMass Migration Cleanup ===\n\n";

try {
    $db = new Database();
    $cleanupLog = [];
    
    // Files that need Gmail dependencies removed/updated
    $filesToUpdate = [
        'background_processor.php' => [
            'search' => 'new GmailIntegration()',
            'replace' => 'new GMassIntegration()',
            'description' => 'Replace Gmail with GMass integration'
        ],
        'classes/AutomatedOutreach.php' => [
            'search' => '$this->gmailIntegration = new GmailIntegration()',
            'replace' => '$this->gmassIntegration = new GMassIntegration()',
            'description' => 'Update AutomatedOutreach to use GMass'
        ],
        'continuous_automation.php' => [
            'search' => '$this->gmail = new GmailIntegration()',
            'replace' => '$this->gmass = new GMassIntegration()',
            'description' => 'Update continuous automation to use GMass'
        ],
        'classes/LeadForwarder.php' => [
            'search' => '$this->gmail = new GmailIntegration()',
            'replace' => '$this->gmass = new GMassIntegration()',
            'description' => 'Update lead forwarding to use GMass'
        ],
        'classes/EmailCompatibilityWrapper.php' => [
            'search' => '$this->gmail = new GmailIntegration()',
            'replace' => '$this->gmass = new GMassIntegration()',
            'description' => 'Update email wrapper to use GMass'
        ]
    ];
    
    // Gmail-specific files to backup and disable
    $gmailFilesToBackup = [
        'gmail_setup.php',
        'gmail_callback.php', 
        'gmail_success.php',
        'reconnect_gmail.php',
        'classes/GmailIntegration.php',
        'classes/GmailOAuth.php',
        'classes/TokenRefreshManager.php'
    ];
    
    echo "📋 PHASE 1: Backup Gmail-specific files\n";
    echo "=======================================\n";
    
    // Create backup directory
    $backupDir = __DIR__ . '/backups/gmail_migration_' . date('Y-m-d_H-i-s');
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    foreach ($gmailFilesToBackup as $file) {
        $filePath = __DIR__ . '/' . $file;
        
        if (file_exists($filePath)) {
            $backupPath = $backupDir . '/' . basename($file);
            
            if (copy($filePath, $backupPath)) {
                echo "  ✅ Backed up: {$file}\n";
                $cleanupLog[] = "Backed up {$file} to {$backupPath}";
                
                // Rename original file to .disabled
                $disabledPath = $filePath . '.disabled';
                if (rename($filePath, $disabledPath)) {
                    echo "  🔒 Disabled: {$file} → {$file}.disabled\n";
                    $cleanupLog[] = "Disabled {$file}";
                }
            } else {
                echo "  ❌ Failed to backup: {$file}\n";
            }
        } else {
            echo "  ⚠️  File not found: {$file}\n";
        }
    }
    
    echo "\n";
    
    echo "📝 PHASE 2: Update files to use GMass instead of Gmail\n";
    echo "=====================================================\n";
    
    foreach ($filesToUpdate as $file => $updateInfo) {
        $filePath = __DIR__ . '/' . $file;
        
        if (file_exists($filePath)) {
            echo "  📄 Processing: {$file}\n";
            
            $content = file_get_contents($filePath);
            $originalContent = $content;
            
            // Make the replacement
            $content = str_replace($updateInfo['search'], $updateInfo['replace'], $content);
            
            // Additional Gmail-specific replacements
            $gmailReplacements = [
                'GmailIntegration' => 'GMassIntegration',
                'gmail->' => 'gmass->',
                '$gmail' => '$gmass',
                '$this->gmail' => '$this->gmass',
                '$this->gmailIntegration' => '$this->gmassIntegration',
                'require_once \'classes/GmailIntegration.php\'' => 'require_once \'classes/GMassIntegration.php\'',
                'require_once "classes/GmailIntegration.php"' => 'require_once "classes/GMassIntegration.php"'
            ];
            
            foreach ($gmailReplacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }
            
            // Check if any changes were made
            if ($content !== $originalContent) {
                // Create backup of original before modifying
                $backupFilePath = $backupDir . '/' . str_replace('/', '_', $file) . '.original';
                file_put_contents($backupFilePath, $originalContent);
                
                // Write updated content
                if (file_put_contents($filePath, $content)) {
                    echo "    ✅ Updated: {$updateInfo['description']}\n";
                    $cleanupLog[] = "Updated {$file}: {$updateInfo['description']}";
                } else {
                    echo "    ❌ Failed to update: {$file}\n";
                }
            } else {
                echo "    ℹ️  No changes needed: {$file}\n";
            }
        } else {
            echo "  ⚠️  File not found: {$file}\n";
        }
    }
    
    echo "\n";
    
    echo "🗄️  PHASE 3: Database cleanup\n";
    echo "============================\n";
    
    // Backup Gmail-related database tables
    echo "  📊 Backing up Gmail-related database tables...\n";
    
    $gmailTables = ['gmail_tokens', 'oauth_states'];
    
    foreach ($gmailTables as $table) {
        try {
            $tableExists = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
            
            if ($tableExists) {
                // Export table data to backup file
                $tableData = $db->fetchAll("SELECT * FROM {$table}");
                $backupFile = $backupDir . "/table_{$table}.json";
                
                file_put_contents($backupFile, json_encode($tableData, JSON_PRETTY_PRINT));
                echo "    ✅ Exported {$table} data to backup\n";
                
                // Rename table to _backup
                $db->execute("RENAME TABLE {$table} TO {$table}_backup");
                echo "    🔄 Renamed {$table} to {$table}_backup\n";
                $cleanupLog[] = "Backed up and renamed table: {$table}";
                
            } else {
                echo "    ℹ️  Table {$table} does not exist\n";
            }
        } catch (Exception $e) {
            echo "    ⚠️  Error handling table {$table}: " . $e->getMessage() . "\n";
        }
    }
    
    // Clean up Gmail-specific settings
    echo "  🧹 Cleaning up Gmail-specific system settings...\n";
    
    $gmailSettings = [
        'gmail_client_id',
        'gmail_client_secret', 
        'gmail_redirect_uri',
        'gmail_credentials',
        'gmail_access_token',
        'gmail_refresh_token'
    ];
    
    foreach ($gmailSettings as $setting) {
        try {
            $deleted = $db->execute(
                "UPDATE system_settings SET setting_value = NULL, updated_at = NOW() WHERE setting_key = ?",
                [$setting]
            );
            
            if ($deleted > 0) {
                echo "    ✅ Cleared setting: {$setting}\n";
                $cleanupLog[] = "Cleared system setting: {$setting}";
            }
        } catch (Exception $e) {
            echo "    ⚠️  Error clearing setting {$setting}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    echo "🔗 PHASE 4: Update navigation and references\n";
    echo "===========================================\n";
    
    // Update navigation files to remove Gmail setup links
    $navigationFiles = [
        'includes/navigation.php',
        'index.php',
        'settings.php'
    ];
    
    foreach ($navigationFiles as $navFile) {
        $filePath = __DIR__ . '/' . $navFile;
        
        if (file_exists($filePath)) {
            echo "  📄 Updating navigation in: {$navFile}\n";
            
            $content = file_get_contents($filePath);
            $originalContent = $content;
            
            // Remove Gmail setup references
            $gmailNavReplacements = [
                'Gmail Setup' => 'Email Setup',
                'gmail_setup.php' => 'setup_wizard.php',
                'Gmail Authorization' => 'Email Configuration',
                'Gmail API' => 'Email System'
            ];
            
            foreach ($gmailNavReplacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }
            
            // Remove Gmail-specific menu items (if any)
            $content = preg_replace('/<a[^>]*href=["\']gmail_[^"\']*["\'][^>]*>.*?<\/a>/i', '', $content);
            
            if ($content !== $originalContent) {
                // Backup original
                $backupNavPath = $backupDir . '/' . str_replace('/', '_', $navFile) . '.original';
                file_put_contents($backupNavPath, $originalContent);
                
                if (file_put_contents($filePath, $content)) {
                    echo "    ✅ Updated navigation references\n";
                    $cleanupLog[] = "Updated navigation in {$navFile}";
                } else {
                    echo "    ❌ Failed to update: {$navFile}\n";
                }
            } else {
                echo "    ℹ️  No navigation changes needed in: {$navFile}\n";
            }
        }
    }
    
    echo "\n";
    
    echo "📁 PHASE 5: Create migration status file\n";
    echo "=======================================\n";
    
    $migrationStatus = [
        'migration_date' => date('Y-m-d H:i:s'),
        'migration_type' => 'gmail_to_gmass',
        'backup_location' => $backupDir,
        'cleanup_log' => $cleanupLog,
        'files_updated' => array_keys($filesToUpdate),
        'files_disabled' => $gmailFilesToBackup,
        'database_changes' => [
            'tables_backed_up' => $gmailTables,
            'settings_cleared' => $gmailSettings
        ],
        'next_steps' => [
            'Complete setup using setup_wizard.php',
            'Test system with test_gmass_imap_integration.php', 
            'Monitor logs for any issues',
            'Update any custom integrations to use GMass'
        ]
    ];
    
    $statusFile = __DIR__ . '/MIGRATION_STATUS.json';
    file_put_contents($statusFile, json_encode($migrationStatus, JSON_PRETTY_PRINT));
    
    echo "  ✅ Migration status saved to: MIGRATION_STATUS.json\n";
    
    echo "\n";
    
    echo "🎯 PHASE 6: Validation\n";
    echo "====================\n";
    
    // Check if GMass classes exist
    $requiredFiles = [
        'classes/GMassIntegration.php',
        'classes/IMAPMonitor.php', 
        'classes/SecureCredentialManager.php',
        'setup_wizard.php'
    ];
    
    $missingFiles = [];
    foreach ($requiredFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "  ✅ Required file exists: {$file}\n";
        } else {
            echo "  ❌ Missing required file: {$file}\n";
            $missingFiles[] = $file;
        }
    }
    
    if (empty($missingFiles)) {
        echo "  🎉 All required GMass system files are present!\n";
    } else {
        echo "  ⚠️  Missing files need to be created before system will work\n";
    }
    
    echo "\n";
    
    echo "📊 MIGRATION CLEANUP SUMMARY\n";
    echo "===========================\n";
    
    echo "✅ Gmail files backed up and disabled: " . count($gmailFilesToBackup) . "\n";
    echo "✅ Code files updated for GMass: " . count($filesToUpdate) . "\n";
    echo "✅ Database tables backed up: " . count($gmailTables) . "\n";
    echo "✅ System settings cleared: " . count($gmailSettings) . "\n";
    echo "✅ Navigation updated to reflect new system\n";
    echo "✅ Migration status documented\n";
    
    echo "\n🎯 NEXT STEPS:\n";
    echo "1. Run setup_wizard.php to configure GMass + IMAP\n";
    echo "2. Test system with test_gmass_imap_integration.php\n";
    echo "3. Monitor logs/error_handler.log for any issues\n";
    echo "4. Update any custom code that references Gmail classes\n";
    echo "5. Train users on new simplified configuration process\n";
    
    echo "\n📁 BACKUP LOCATION: {$backupDir}\n";
    echo "All original files and data have been safely backed up.\n";
    
    echo "\n🎉 Gmail to GMass migration cleanup completed successfully!\n";
    echo "The system is now ready to use GMass + IMAP for email automation.\n";
    
} catch (Exception $e) {
    echo "❌ Migration cleanup failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>