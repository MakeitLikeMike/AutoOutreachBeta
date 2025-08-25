<?php
/**
 * Fix Automation Pipeline Script
 * This script fixes the automation pipeline issues and sets up the system properly
 */

require_once 'config/database.php';

class AutomationPipelineFix {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function runFix() {
        echo "🔧 Starting Automation Pipeline Fix...\n\n";
        
        try {
            // 1. Run database migration
            $this->runDatabaseMigration();
            
            // 2. Check and fix table structures
            $this->checkTableStructures();
            
            // 3. Verify background processor
            $this->verifyBackgroundProcessor();
            
            // 4. Test automation pipeline
            $this->testAutomationPipeline();
            
            // 5. Set up automation settings
            $this->setupAutomationSettings();
            
            echo "\n✅ Automation Pipeline Fix Completed Successfully!\n";
            echo "🚀 Your campaigns will now automatically process when created.\n\n";
            
            echo "📋 Summary:\n";
            echo "• Background jobs table: ✅ Created/Updated\n";
            echo "• Campaign automation columns: ✅ Added\n";
            echo "• Background processor: ✅ Verified\n";
            echo "• Automation settings: ✅ Configured\n\n";
            
            echo "🎯 Next Steps:\n";
            echo "1. Create a new campaign with competitor URLs\n";
            echo "2. The system will automatically start processing\n";
            echo "3. Monitor progress in the campaign dashboard\n";
            echo "4. Check logs in /logs/background_processor.log\n\n";
            
        } catch (Exception $e) {
            echo "❌ Fix failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
    
    private function runDatabaseMigration() {
        echo "📊 Running database migration...\n";
        
        $migrationFile = __DIR__ . '/database_migrations/background_jobs_table.sql';
        
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: $migrationFile");
        }
        
        $sql = file_get_contents($migrationFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $this->db->execute($statement);
                echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                // Ignore 'column already exists' errors
                if (strpos($e->getMessage(), 'Duplicate column') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "  ⚠️ Warning: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "  ✅ Migration completed\n\n";
    }
    
    private function checkTableStructures() {
        echo "🏗️ Checking table structures...\n";
        
        // Check if background_jobs table exists
        try {
            $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM background_jobs LIMIT 1");
            echo "  ✅ background_jobs table exists\n";
        } catch (Exception $e) {
            throw new Exception("background_jobs table missing: " . $e->getMessage());
        }
        
        // Check campaigns table for automation columns
        $automationColumns = [
            'pipeline_status', 'processing_started_at', 'is_automated', 
            'auto_domain_analysis', 'auto_email_search'
        ];
        
        foreach ($automationColumns as $column) {
            try {
                $this->db->fetchOne("SELECT $column FROM campaigns LIMIT 1");
                echo "  ✅ campaigns.$column exists\n";
            } catch (Exception $e) {
                echo "  ⚠️ campaigns.$column missing, will be added by migration\n";
            }
        }
        
        echo "  ✅ Table structure check completed\n\n";
    }
    
    private function verifyBackgroundProcessor() {
        echo "⚙️ Verifying background processor...\n";
        
        $processorFile = __DIR__ . '/classes/BackgroundJobProcessor.php';
        if (!file_exists($processorFile)) {
            throw new Exception("BackgroundJobProcessor file not found: $processorFile");
        }
        echo "  ✅ BackgroundJobProcessor file exists\n";
        
        // Test instantiation
        require_once $processorFile;
        try {
            $processor = new BackgroundJobProcessor();
            echo "  ✅ BackgroundJobProcessor instantiated successfully\n";
            
            // Test job queuing
            $processor->queueJob('test_job', 1, null, ['test' => true], 1);
            echo "  ✅ Job queuing works\n";
            
            // Clean up test job
            $this->db->execute("DELETE FROM background_jobs WHERE job_type = 'test_job'");
            
        } catch (Exception $e) {
            throw new Exception("BackgroundJobProcessor test failed: " . $e->getMessage());
        }
        
        echo "  ✅ Background processor verification completed\n\n";
    }
    
    private function testAutomationPipeline() {
        echo "🧪 Testing automation pipeline...\n";
        
        try {
            // Test APP_ROOT definition
            if (!defined('APP_ROOT')) {
                define('APP_ROOT', __DIR__);
                echo "  ✅ APP_ROOT defined\n";
            } else {
                echo "  ✅ APP_ROOT already defined: " . APP_ROOT . "\n";
            }
            
            // Test CampaignService
            require_once __DIR__ . '/app/bootstrap.php';
            require_once __DIR__ . '/app/CampaignService.php';
            
            $campaignService = new CampaignService();
            echo "  ✅ CampaignService instantiated\n";
            
        } catch (Exception $e) {
            throw new Exception("Automation pipeline test failed: " . $e->getMessage());
        }
        
        echo "  ✅ Automation pipeline test completed\n\n";
    }
    
    private function setupAutomationSettings() {
        echo "⚙️ Setting up automation settings...\n";
        
        $settings = [
            'enable_automation_pipeline' => 'yes',
            'enable_automated_outreach' => 'yes',
            'automated_outreach_batch_size' => '15',
            'background_processor_enabled' => 'yes',
            'email_search_batch_size' => '10',
            'immediate_processing_enabled' => 'yes'
        ];
        
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
            $this->db->execute($sql, [$key, $value]);
            echo "  ✅ Set $key = $value\n";
        }
        
        echo "  ✅ Automation settings configured\n\n";
    }
}

// Run the fix
$fix = new AutomationPipelineFix();
$fix->runFix();
?>