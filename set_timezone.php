<?php
/**
 * Set user timezone via AJAX
 */
require_once 'classes/TimezoneManager.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['timezone'])) {
        throw new Exception('Timezone not provided');
    }
    
    $timezone = $input['timezone'];
    TimezoneManager::setUserTimezone($timezone);
    
    echo json_encode([
        'success' => true,
        'timezone' => $timezone,
        'message' => 'Timezone set successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>