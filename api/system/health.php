<?php
/**
 * System Health API Endpoint
 * Provides real-time system health and status information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/supabase.php';

try {
    $health = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'services' => []
    ];
    
    // Check database connection
    try {
        $db = Database::getInstance();
        $db->query('SELECT 1');
        $health['services']['database'] = [
            'status' => 'healthy',
            'response_time' => microtime(true)
        ];
    } catch (Exception $e) {
        $health['services']['database'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // Check Supabase connection
    try {
        $supabase = new SupabaseClient();
        $supabase->request('users', 'GET');
        $health['services']['supabase'] = [
            'status' => 'healthy',
            'response_time' => microtime(true)
        ];
    } catch (Exception $e) {
        $health['services']['supabase'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // Check file system
    try {
        $uploadDir = __DIR__ . '/../../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        if (is_writable($uploadDir)) {
            $health['services']['filesystem'] = [
                'status' => 'healthy',
                'upload_dir' => $uploadDir
            ];
        } else {
            $health['services']['filesystem'] = [
                'status' => 'warning',
                'message' => 'Upload directory not writable'
            ];
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        }
    } catch (Exception $e) {
        $health['services']['filesystem'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $health['status'] = 'degraded';
    }
    
    // Check PHP environment
    $health['services']['php'] = [
        'status' => 'healthy',
        'version' => PHP_VERSION,
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_pgsql' => extension_loaded('pdo_pgsql'),
            'curl' => extension_loaded('curl'),
            'json' => extension_loaded('json')
        ]
    ];
    
    // Check memory usage
    $memoryLimit = ini_get('memory_limit');
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    
    $health['services']['memory'] = [
        'status' => 'healthy',
        'limit' => $memoryLimit,
        'current_usage' => $memoryUsage,
        'peak_usage' => $memoryPeak
    ];
    
    // Set HTTP status code based on overall health
    if ($health['status'] === 'healthy') {
        http_response_code(200);
    } elseif ($health['status'] === 'warning') {
        http_response_code(200); // Still operational
    } else {
        http_response_code(503); // Service unavailable
    }
    
    echo json_encode($health);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
