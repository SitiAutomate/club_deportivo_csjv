<?php

/**
 * Logger simple para errores y eventos del backend.
 * Escribe en logs/app.log (crea el directorio si no existe).
 */
class AppLogger
{
    private static ?string $logDir = null;

    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            $base = defined('PROJECT_ROOT') ? constant('PROJECT_ROOT') : dirname(__DIR__);
            self::$logDir = rtrim($base, '/') . '/logs';
        }
        return self::$logDir;
    }

    /**
     * Escribir en log. Nivel: error, warning, info, debug
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = self::getLogDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/app.log';
        $ts = date('Y-m-d H:i:s');
        $ctx = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf("[%s] [%s] %s%s\n", $ts, strtoupper($level), $message, $ctx);
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        if ($level === 'error') {
            error_log($message . $ctx);
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }
}
