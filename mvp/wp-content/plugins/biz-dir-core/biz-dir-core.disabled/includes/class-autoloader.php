<?php
/**
 * Autoloader Class
 *
 * @package BizDir\Core
 */

namespace BizDir\Core;

/**
 * Prevent direct access
 */
if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {
    /**
     * Debug mode
     *
     * @var bool
     */
    private $debug = true;

    /**
     * Debug prefix for log messages
     * 
     * @var string
     */
    private $debug_prefix = '[BizDir Autoloader] ';

    /**
     * Register the autoloader
     */
    public function register() {
        if ($this->debug) {
            error_log($this->debug_prefix . 'Registering autoloader with SPL');
            error_log($this->debug_prefix . 'Include path: ' . get_include_path());
            error_log($this->debug_prefix . 'Plugin dir: ' . BIZ_DIR_PLUGIN_DIR);
        }
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class_name Full class name including namespace
     */
    private function autoload($class_name) {
        // Only handle our namespace
        if (strpos($class_name, 'BizDir\\') !== 0) {
            if ($this->debug) {
                error_log("Skipping class not in BizDir namespace: $class_name");
            }
            return;
        }

        if ($this->debug) {
            error_log("Attempting to load class: $class_name");
        }

        // Convert namespace to file path
        $file_path = str_replace(
            ['BizDir\\Core\\', 'BizDir\\', '\\'],
            ['', '', DIRECTORY_SEPARATOR],
            $class_name
        );

        if ($this->debug) {
            error_log("Path after namespace conversion: $file_path");
        }

        // Convert class name to file name format
        $file_path = strtolower(
            preg_replace(
                ['/([a-z])([A-Z])/', '/_/', '/\\\\/', '/\s+/'],
                ['$1-$2', '-', DIRECTORY_SEPARATOR, '-'],
                $file_path
            )
        );

        if ($this->debug) {
            error_log("Final converted path: $file_path");
        }

        // Build full path, handle subdirectories
        $file = BIZ_DIR_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR;
        $parts = explode('/', $file_path);
        $class_name = array_pop($parts); // Get the last part as the class name
        if (!empty($parts)) {
            $file .= implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR;
        }
        $file .= 'class-' . $class_name . '.php';

        if ($this->debug) {
            error_log("Looking for file: $file");
        }

        // Require file if exists
        if (file_exists($file)) {
            if ($this->debug) {
                error_log("Loading file: $file");
            }
            require_once $file;
        } else {
            if ($this->debug) {
                error_log("File not found: $file");
            }
        }
    }
}
