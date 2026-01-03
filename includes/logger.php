<?php
// includes/logger.php

class Logger {
    private static $logFile = 'app.log';

    public static function log($message, $level = 'INFO', $context = []) {
        // Definiowanie ścieżki do logów
        if (!defined('LOGS_PATH')) {
            define('LOGS_PATH', dirname(__DIR__) . '/logs');
        }
        
        // Tworzenie folderu jeśli nie istnieje
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        $filepath = LOGS_PATH . '/' . self::$logFile;
        $date = date('Y-m-d H:i:s');
        
        // Formatowanie danych (tablice/obiekty do stringa)
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }

        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[$date] [$level] $message $contextStr" . PHP_EOL;

        // Zapis do pliku
        file_put_contents($filepath, $logLine, FILE_APPEND);
    }

    public static function error($message, $context = []) {
        self::log($message, 'ERROR', $context);
    }

    public static function debug($message, $context = []) {
        self::log($message, 'DEBUG', $context);
    }
}

// Globalne funkcje pomocnicze
function logger($data, $description = '') {
    $msg = $description ? "$description: " : "";
    Logger::debug($msg . print_r($data, true));
}

// Przechwytywanie błędów PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    Logger::error("PHP Error [$errno]: $errstr in $errfile:$errline");
    return false;
});

set_exception_handler(function($e) {
    Logger::error("Uncaught Exception: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString());
});
?>