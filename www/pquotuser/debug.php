<?php
require_once('config.php');

// Definir la ruta del archivo de log
define('DEBUG_LOG_FILE', __DIR__ . '/debug.log');

if (!function_exists('debugLog')) {
    function debugLog($message, $data = null) {
        if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
            try {
                // Preparar el mensaje
                $timestamp = date('Y-m-d H:i:s');
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
                $file = basename($backtrace[0]['file']);
                $line = $backtrace[0]['line'];

                $logMessage = "[{$timestamp}][{$file}:{$line}] {$message}";
                if ($data !== null) {
                    $logMessage .= "\nData: " . print_r($data, true);
                }
                $logMessage .= "\n";

                // Escribir en el archivo
                error_log($logMessage, 3, DEBUG_LOG_FILE);

            } catch (Exception $e) {
                error_log("Error en sistema de debug: " . $e->getMessage());
            }
        }
    }
}
?>