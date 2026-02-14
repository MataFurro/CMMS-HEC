<?php

namespace Backend\Core;

/**
 * LoggerService.php
 * ─────────────────────────────────────────────────────
 * Sistema de logging estructurado para BioCMMS.
 * ─────────────────────────────────────────────────────
 */
class LoggerService
{
    private static string $logPath = __DIR__ . '/../../storage/logs/app.log';

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    private static function log(string $level, string $message, array $context): void
    {
        $dir = dirname(self::$logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user' => $_SESSION['user_id'] ?? 'anonymous',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
        ];

        file_put_contents(self::$logPath, json_encode($entry) . PHP_EOL, FILE_APPEND);
    }
}
