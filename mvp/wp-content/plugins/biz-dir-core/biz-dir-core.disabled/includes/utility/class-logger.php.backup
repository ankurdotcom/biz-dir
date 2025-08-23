<?php
/**
 * Logger Class for BizDir
 *
 * @package BizDir\Core\Utility
 */

namespace BizDir\Core\Utility;

class Logger {
    /**
     * Prefix for log messages
     *
     * @var string
     */
    private static $prefix = '[BizDir]';

    /**
     * Log a debug message
     *
     * @param string $message The message to log
     * @param array  $context Optional context data
     */
    public static function debug($message, array $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $formatted_message = self::format_message('DEBUG', $message, $context);
            error_log($formatted_message);
        }
    }

    /**
     * Log an info message
     *
     * @param string $message The message to log
     * @param array  $context Optional context data
     */
    public static function info($message, array $context = []) {
        $formatted_message = self::format_message('INFO', $message, $context);
        error_log($formatted_message);
    }

    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param array  $context Optional context data
     */
    public static function warning($message, array $context = []) {
        $formatted_message = self::format_message('WARNING', $message, $context);
        error_log($formatted_message);
    }

    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param array  $context Optional context data
     */
    public static function error($message, array $context = []) {
        $formatted_message = self::format_message('ERROR', $message, $context);
        error_log($formatted_message);
    }

    /**
     * Format a log message with context
     *
     * @param string $level   Log level
     * @param string $message The message to format
     * @param array  $context Context data
     * @return string
     */
    private static function format_message($level, $message, array $context) {
        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        $memory = round(memory_get_usage() / 1024 / 1024, 2);
        
        $formatted = sprintf(
            "%s[PID:%d][MEM:%.2fMB][%s] %s",
            self::$prefix,
            $pid,
            $memory,
            $level,
            $message
        );

        if (!empty($context)) {
            $formatted .= ' | ' . json_encode($context);
        }

        return $formatted;
    }
}
