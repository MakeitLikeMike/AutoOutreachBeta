<?php
/**
 * Backfill GPT Analysis for Approved Domains
 * This script runs GPT analysis on approved domains that don't have AI data
 */

require_once 'config/database.php';
require_once 'classes/TargetDomain.php';
require_once 'classes/ChatGPTIntegration.php';

echo "=== Backfill GPT Analysis for Approved Domains ===" . PHP_EOL;

try {
    $db = new Database();
    $targetDomain = new TargetDomain();
    $chatgpt = new ChatGPTIntegration();
    
    // Get approved domains without AI analysis
    $domainsNeedingAnalysis = $db->fetchAll("
        SELECT id, domain, status 
        FROM target_domains 
        WHERE status = 'approved' 
        AND (ai_recommendations IS NULL OR ai_recommendations = '' OR ai_analysis_status = 'pending')
        ORDER BY id ASC
        LIMIT 10
    ");
    
    if (empty($domainsNeedingAnalysis)) {
        echo "No approved domains found that need GPT analysis." . PHP_EOL;
        exit;
    }
    
    echo "Found " . count($domainsNeedingAnalysis) . " approved domains that need GPT analysis:" . PHP_EOL;
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($domainsNeedingAnalysis as $domain) {
        echo PHP_EOL . "Processing domain ID {$domain['id']}: {$domain['domain']}" . PHP_EOL;
        
        try {
            // Mark as analyzing to prevent duplicate requests
            $targetDomain->updateAIAnalysis($domain['id'], ['ai_analysis_status' => 'analyzing']);
            
            // Run GPT analysis
            echo "  Running GPT analysis..." . PHP_EOL;
            $result = $chatgpt->analyzeGuestPostSuitability($domain['domain']);
            
            if ($result['success']) {
                $structured = $result['structured_analysis'] ?? [];
                $updateData = [
                    'ai_analysis_status' => 'completed',
                    'ai_overall_score' => $structured['overall_score'] ?? null,
                    'ai_guest_post_score' => $structured['guest_post_score'] ?? $structured['overall_score'] ?? null,
                    'ai_content_quality_score' => $structured['content_quality_score'] ?? null,
                    'ai_audience_alignment_score' => $structured['audience_alignment_score'] ?? null,
                    'ai_priority_level' => $structured['priority_level'] ?? null,
                    'ai_recommendations' => $result['raw_analysis'] ?? $structured['summary'] ?? null,
                    'ai_last_analyzed_at' => date('Y-m-d H:i:s')
                ];
                
                $targetDomain->updateAIAnalysis($domain['id'], $updateData);
                
                echo "  ✅ SUCCESS: GPT analysis completed" . PHP_EOL;
                echo "     Overall Score: " . ($updateData['ai_overall_score'] ?? 'N/A') . PHP_EOL;
                echo "     Recommendations: " . (strlen($updateData['ai_recommendations'] ?? '') > 0 ? 'Generated' : 'Empty') . PHP_EOL;
                
                $successCount++;
                
                // Add a small delay to respect rate limits
                sleep(2);
                
            } else {
                $targetDomain->updateAIAnalysis($domain['id'], ['ai_analysis_status' => 'failed']);
                echo "  ❌ FAILED: " . ($result['error'] ?? 'Unknown error') . PHP_EOL;
                $failCount++;
            }
            
        } catch (Exception $e) {
            $targetDomain->updateAIAnalysis($domain['id'], ['ai_analysis_status' => 'failed']);
            echo "  ❌ EXCEPTION: " . $e->getMessage() . PHP_EOL;
            $failCount++;
        }
    }
    
    echo PHP_EOL . "=== Backfill Complete ===" . PHP_EOL;
    echo "Successful: $successCount" . PHP_EOL;
    echo "Failed: $failCount" . PHP_EOL;
    echo "Total processed: " . ($successCount + $failCount) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Script error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
?>