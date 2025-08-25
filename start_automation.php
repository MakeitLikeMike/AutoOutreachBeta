<?php
/**
 * Start Continuous Automation Pipeline
 * This script starts the automation pipeline in the background
 */

echo "🚀 Starting Continuous Automation Pipeline..." . PHP_EOL;

// Get the PHP executable path
$phpPath = PHP_BINARY;
$scriptPath = __DIR__ . '/continuous_automation.php';

// For Windows, start the process in the background
if (PHP_OS_FAMILY === 'Windows') {
    $command = "start /B \"Automation Pipeline\" \"$phpPath\" \"$scriptPath\"";
    exec($command);
    echo "✅ Automation pipeline started in background on Windows" . PHP_EOL;
} else {
    // For Linux/Unix systems
    $command = "nohup \"$phpPath\" \"$scriptPath\" > /dev/null 2>&1 &";
    exec($command);
    echo "✅ Automation pipeline started in background on Unix/Linux" . PHP_EOL;
}

echo "📋 Process will run continuously and process domains automatically" . PHP_EOL;
echo "📁 Check logs/continuous_automation.log for detailed output" . PHP_EOL;
echo "🛑 To stop: use Task Manager (Windows) or 'pkill -f continuous_automation.php' (Unix)" . PHP_EOL;
?>