<?php
/**
 * Logging Configuration
 * Comprehensive logging system for debugging and monitoring
 */

class Logger {
    private $logFile;
    private $logLevel;
    private $maxFileSize;
    private $maxFiles;
    
    // Log levels
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    
    public function __construct($logFile = null, $logLevel = self::LEVEL_INFO) {
        $this->logFile = $logFile ?: dirname(__DIR__) . '/logs/app.log';
        $this->logLevel = $logLevel;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->maxFiles = 5;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Rotate logs if needed
        $this->rotateLogs();
    }
    
    /**
     * Log a debug message
     */
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, 'DEBUG', $message, $context);
    }
    
    /**
     * Log an info message
     */
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, 'INFO', $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, 'WARNING', $message, $context);
    }
    
    /**
     * Log an error message
     */
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, 'ERROR', $message, $context);
    }
    
    /**
     * Log a critical message
     */
    public function critical($message, $context = []) {
        $this->log(self::LEVEL_CRITICAL, 'CRITICAL', $message, $context);
    }
    
    /**
     * Log API requests
     */
    public function logApiRequest($method, $endpoint, $params = [], $response = null, $duration = null) {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'params' => $this->sanitizeParams($params),
            'response_code' => $response ? http_response_code() : null,
            'duration_ms' => $duration,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => $this->getClientIP(),
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'user_role' => $_SESSION['user_role'] ?? 'guest'
        ];
        
        $this->info("API Request: {$method} {$endpoint}", $context);
    }
    
    /**
     * Log API errors
     */
    public function logApiError($method, $endpoint, $error, $params = []) {
        $context = [
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $error,
            'params' => $this->sanitizeParams($params),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'ip_address' => $this->getClientIP(),
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'user_role' => $_SESSION['user_role'] ?? 'guest',
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        $this->error("API Error: {$method} {$endpoint} - {$error}", $context);
    }
    
    /**
     * Log database operations
     */
    public function logDatabaseOperation($operation, $table, $query, $params = [], $duration = null) {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'query' => $query,
            'params' => $this->sanitizeParams($params),
            'duration_ms' => $duration,
            'user_id' => $_SESSION['user_id'] ?? 'guest'
        ];
        
        $this->info("Database: {$operation} on {$table}", $context);
    }
    
    /**
     * Log database errors
     */
    public function logDatabaseError($operation, $table, $query, $error, $params = []) {
        $context = [
            'operation' => $operation,
            'table' => $table,
            'query' => $query,
            'error' => $error,
            'params' => $this->sanitizeParams($params),
            'user_id' => $_SESSION['user_id'] ?? 'guest'
        ];
        
        $this->error("Database Error: {$operation} on {$table} - {$error}", $context);
    }
    
    /**
     * Log authentication events
     */
    public function logAuthEvent($event, $username = null, $success = true, $details = []) {
        $context = [
            'event' => $event,
            'username' => $username,
            'success' => $success,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'details' => $details
        ];
        
        if ($success) {
            $this->info("Auth: {$event} successful", $context);
        } else {
            $this->warning("Auth: {$event} failed", $context);
        }
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($event, $severity = 'medium', $details = []) {
        $context = [
            'event' => $event,
            'severity' => $severity,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'details' => $details
        ];
        
        switch ($severity) {
            case 'low':
                $this->info("Security: {$event}", $context);
                break;
            case 'medium':
                $this->warning("Security: {$event}", $context);
                break;
            case 'high':
                $this->error("Security: {$event}", $context);
                break;
            case 'critical':
                $this->critical("Security: {$event}", $context);
                break;
        }
    }
    
    /**
     * Log file operations
     */
    public function logFileOperation($operation, $filename, $size = null, $success = true) {
        $context = [
            'operation' => $operation,
            'filename' => $filename,
            'size_bytes' => $size,
            'success' => $success,
            'user_id' => $_SESSION['user_id'] ?? 'guest'
        ];
        
        if ($success) {
            $this->info("File: {$operation} {$filename}", $context);
        } else {
            $this->error("File: {$operation} {$filename} failed", $context);
        }
    }
    
    /**
     * Main logging method
     */
    private function log($level, $levelName, $message, $context = []) {
        if ($level < $this->logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'message' => $message,
            'context' => $context
        ];
        
        // Format log entry
        $formattedEntry = $this->formatLogEntry($logEntry);
        
        // Write to log file
        file_put_contents($this->logFile, $formattedEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for critical errors
        if ($level >= self::LEVEL_CRITICAL) {
            error_log("CRITICAL: {$message}");
        }
    }
    
    /**
     * Format log entry
     */
    private function formatLogEntry($entry) {
        $contextStr = !empty($entry['context']) ? ' ' . json_encode($entry['context']) : '';
        return "[{$entry['timestamp']}] {$entry['level']}: {$entry['message']}{$contextStr}";
    }
    
    /**
     * Sanitize parameters for logging (remove sensitive data)
     */
    private function sanitizeParams($params) {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth'];
        $sanitized = $params;
        
        foreach ($sensitiveFields as $field) {
            if (isset($sanitized[$field])) {
                $sanitized[$field] = '***REDACTED***';
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Rotate log files
     */
    private function rotateLogs() {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        if (filesize($this->logFile) < $this->maxFileSize) {
            return;
        }
        
        // Rotate existing logs
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logFile . ".{$i}";
            $newFile = $this->logFile . "." . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i + 1 >= $this->maxFiles) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Move current log
        rename($this->logFile, $this->logFile . '.1');
        
        // Create new log file
        touch($this->logFile);
        chmod($this->logFile, 0644);
    }
    
    /**
     * Get log file path
     */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /**
     * Set log level
     */
    public function setLogLevel($level) {
        $this->logLevel = $level;
    }
    
    /**
     * Get current log level
     */
    public function getLogLevel() {
        return $this->logLevel;
    }
}

// Global logger instance
$logger = new Logger();

// Helper functions for easy logging
function log_debug($message, $context = []) {
    global $logger;
    $logger->debug($message, $context);
}

function log_info($message, $context = []) {
    global $logger;
    $logger->info($message, $context);
}

function log_warning($message, $context = []) {
    global $logger;
    $logger->warning($message, $context);
}

function log_error($message, $context = []) {
    global $logger;
    $logger->error($message, $context);
}

function log_critical($message, $context = []) {
    global $logger;
    $logger->critical($message, $context);
}

function log_api_request($method, $endpoint, $params = [], $response = null, $duration = null) {
    global $logger;
    $logger->logApiRequest($method, $endpoint, $params, $response, $duration);
}

function log_api_error($method, $endpoint, $error, $params = []) {
    global $logger;
    $logger->logApiError($method, $endpoint, $error, $params);
}

function log_db_operation($operation, $table, $query, $params = [], $duration = null) {
    global $logger;
    $logger->logDatabaseOperation($operation, $table, $query, $params, $duration);
}

function log_db_error($operation, $table, $query, $error, $params = []) {
    global $logger;
    $logger->logDatabaseError($operation, $table, $query, $error, $params);
}

function log_auth_event($event, $username = null, $success = true, $details = []) {
    global $logger;
    $logger->logAuthEvent($event, $username, $success, $details);
}

function log_security_event($event, $severity = 'medium', $details = []) {
    global $logger;
    $logger->logSecurityEvent($event, $severity, $details);
}

function log_file_operation($operation, $filename, $size = null, $success = true) {
    global $logger;
    $logger->logFileOperation($operation, $filename, $size, $success);
}
?>
