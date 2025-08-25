<?php
/**
 * System Startup Script
 * Use this to start the outreach automation system
 */

echo "🚀 Starting Outreach Automation System...\n";

// Check if background processor is already running
require_once 'config/database.php';
$db = new Database();

// Check recent activity
$recentActivity = $db->fetchOne("
    SELECT COUNT(*) as recent_jobs 
    FROM background_jobs 
    WHERE updated_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
");

if ($recentActivity['recent_jobs'] > 0) {
    echo "✅ Background processor is already running!\n";
    echo "Recent activity: {$recentActivity['recent_jobs']} jobs processed in last 2 minutes\n";
} else {
    echo "⚠️ Background processor not running. Starting it now...\n";
    
    // For Windows (XAMPP)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Start background processor in new window
        $command = 'start "Outreach Background Processor" /MIN C:/xampp/php/php.exe run_background_processor.php';
        pclose(popen($command, 'r'));
        echo "✅ Background processor started in minimized window\n";
    } else {
        // For Linux/Mac (hosting)
        $command = 'nohup php run_background_processor.php > /dev/null 2>&1 &';
        exec($command);
        echo "✅ Background processor started as daemon\n";
    }
    
    // Wait a moment for startup
    sleep(2);
}

// Show system status
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_jobs,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing
    FROM background_jobs
");

echo "\n📊 System Status:\n";
echo "Total jobs: {$stats['total_jobs']}\n";
echo "Pending jobs: {$stats['pending']}\n";
echo "Currently processing: {$stats['processing']}\n";

// Show campaigns
$campaigns = $db->fetchAll("SELECT id, name, status FROM campaigns WHERE status = 'active'");
echo "\n🎯 Active Campaigns: " . count($campaigns) . "\n";
foreach ($campaigns as $campaign) {
    echo "- Campaign {$campaign['id']}: {$campaign['name']}\n";
}

echo "\n✅ Outreach Automation System is now running!\n";
echo "💡 Keep this system running for continuous processing.\n";
echo "📝 Monitor progress at: http://localhost/Autooutreach/domains.php\n";
echo "📊 Check monitoring at: http://localhost/Autooutreach/monitoring.php\n";
?>