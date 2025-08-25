<?php
/**
 * Initialize IMAP Reply Monitoring
 * Starts the IMAP monitoring background job
 */
require_once 'config/database.php';
require_once 'classes/BackgroundJobProcessor.php';

echo "🚀 Starting IMAP Reply Monitoring System\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "========================================\n\n";

try {
    $db = new Database();
    $processor = new BackgroundJobProcessor();
    
    // Check if IMAP settings are configured
    $imapSettings = $db->fetchAll("
        SELECT setting_key, setting_value 
        FROM system_settings 
        WHERE setting_key IN ('imap_email', 'imap_app_password', 'gmass_api_key')
    ");
    
    $settings = [];
    foreach ($imapSettings as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Validate configuration
    $missingSettings = [];
    if (empty($settings['imap_email'])) $missingSettings[] = 'IMAP Email';
    if (empty($settings['imap_app_password'])) $missingSettings[] = 'IMAP App Password';
    if (empty($settings['gmass_api_key'])) $missingSettings[] = 'GMass API Key';
    
    if (!empty($missingSettings)) {
        echo "❌ Missing configuration:\n";
        foreach ($missingSettings as $missing) {
            echo "   - {$missing}\n";
        }
        echo "\nPlease configure these settings in settings_gmass.php\n";
        exit(1);
    }
    
    echo "✅ Configuration validated\n";
    echo "   - IMAP Email: {$settings['imap_email']}\n";
    echo "   - GMass API: Configured\n\n";
    
    // Test IMAP connection
    echo "🔗 Testing IMAP connection...\n";
    require_once 'classes/IMAPMonitor.php';
    $imapMonitor = new IMAPMonitor();
    $testResult = $imapMonitor->testConnection();
    
    if (!$testResult['success']) {
        echo "❌ IMAP connection test failed: {$testResult['error']}\n";
        echo "Please check your IMAP credentials and try again.\n";
        exit(1);
    }
    
    echo "✅ IMAP connection successful\n";
    echo "   - Host: {$testResult['host']}:{$testResult['port']}\n";
    echo "   - Email: {$testResult['email']}\n\n";
    
    // Check if IMAP monitoring job already exists
    $existingJob = $db->fetchOne("
        SELECT id, status, created_at 
        FROM background_jobs 
        WHERE job_type = 'monitor_imap_replies' 
        AND status IN ('pending', 'processing') 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    if ($existingJob) {
        echo "ℹ️ IMAP monitoring job already exists (ID: {$existingJob['id']})\n";
        echo "   Status: {$existingJob['status']}\n";
        echo "   Created: {$existingJob['created_at']}\n\n";
    } else {
        // Create initial IMAP monitoring job
        echo "📋 Creating IMAP monitoring job...\n";
        
        $processor->queueJob(
            'monitor_imap_replies',
            null, // campaign_id (not needed for IMAP monitoring)
            null, // domain_id (not needed for IMAP monitoring)
            [],   // payload (empty)
            2,    // priority (medium)
            date('Y-m-d H:i:s') // immediate start
        );
        
        echo "✅ IMAP monitoring job created\n\n";
    }
    
    // Start background processor if not running
    echo "🔄 Checking background processor status...\n";
    
    $runningProcesses = [];
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        exec('tasklist /FI "IMAGENAME eq php.exe" /FO CSV', $output);
        $runningProcesses = $output;
    } else {
        // Unix/Linux
        exec('ps aux | grep "run_background_processor.php" | grep -v grep', $runningProcesses);
    }
    
    $processorRunning = false;
    foreach ($runningProcesses as $process) {
        if (strpos($process, 'run_background_processor.php') !== false) {
            $processorRunning = true;
            break;
        }
    }
    
    if ($processorRunning) {
        echo "✅ Background processor is already running\n";
    } else {
        echo "⚠️ Background processor not detected\n";
        echo "Starting background processor...\n";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $cmd = 'start /b php run_background_processor.php';
        } else {
            // Unix/Linux
            $cmd = 'nohup php run_background_processor.php > /dev/null 2>&1 &';
        }
        
        exec($cmd);
        echo "✅ Background processor started\n";
    }
    
    echo "\n🎉 IMAP Monitoring System Initialized!\n\n";
    echo "📊 System Status:\n";
    echo "   - GMass Integration: ✅ Active\n";
    echo "   - IMAP Monitoring: ✅ Active\n";
    echo "   - Background Jobs: ✅ Processing\n";
    echo "   - Check Interval: 15 minutes\n\n";
    
    echo "📋 What happens next:\n";
    echo "   1. System monitors {$settings['imap_email']} inbox every 15 minutes\n";
    echo "   2. New replies are classified automatically\n";
    echo "   3. Positive leads are forwarded immediately\n";
    echo "   4. All activity is logged for tracking\n\n";
    
    echo "📁 Log Files:\n";
    echo "   - IMAP Monitor: logs/imap_monitor.log\n";
    echo "   - Reply Processing: logs/reply_monitor.log\n";
    echo "   - Background Jobs: logs/background_processor.log\n\n";
    
    echo "🔧 Configuration Interface: settings_gmass.php\n";
    echo "📊 System Status: system_status.php\n";
    
} catch (Exception $e) {
    echo "❌ Initialization failed: " . $e->getMessage() . "\n";
    echo "Please check your configuration and try again.\n";
    exit(1);
}

echo "\n✅ IMAP monitoring system is now active and running!\n";
?>