<?php
/**
 * Essential WordPress Functions Mock
 * 
 * Industry-standard mock implementation of essential WordPress functions
 * Following OWASP security guidelines and IEEE testing standards
 * 
 * @package BizDir
 * @subpackage Tests\Mocks
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('BIZ_DIR_TESTING_MODE')) {
    exit('Direct access not allowed');
}

/**
 * WordPress Core Functions Mock Implementation
 */

if (!function_exists('add_action')) {
    /**
     * Mock add_action function
     * 
     * @param string $hook_name Action hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @param int $accepted_args Number of accepted arguments
     * @return true Always returns true
     */
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        return MockWordPressHooks::add_action($hook_name, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('add_filter')) {
    /**
     * Mock add_filter function
     * 
     * @param string $hook_name Filter hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @param int $accepted_args Number of accepted arguments
     * @return true Always returns true
     */
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        return MockWordPressHooks::add_filter($hook_name, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('do_action')) {
    /**
     * Mock do_action function
     * 
     * @param string $hook_name Action hook name
     * @param mixed ...$args Arguments to pass
     * @return void
     */
    function do_action(string $hook_name, ...$args): void {
        MockWordPressHooks::do_action($hook_name, ...$args);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Mock apply_filters function
     * 
     * @param string $hook_name Filter hook name
     * @param mixed $value Value to filter
     * @param mixed ...$args Additional arguments
     * @return mixed Filtered value
     */
    function apply_filters(string $hook_name, $value, ...$args): mixed {
        return MockWordPressHooks::apply_filters($hook_name, $value, ...$args);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Mock remove_action function
     * 
     * @param string $hook_name Action hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @return bool True if removed, false otherwise
     */
    function remove_action(string $hook_name, callable $callback, int $priority = 10): bool {
        return MockWordPressHooks::remove_action($hook_name, $callback, $priority);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * Mock remove_filter function
     * 
     * @param string $hook_name Filter hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     * @return bool True if removed, false otherwise
     */
    function remove_filter(string $hook_name, callable $callback, int $priority = 10): bool {
        return MockWordPressHooks::remove_filter($hook_name, $callback, $priority);
    }
}

if (!function_exists('current_time')) {
    /**
     * Mock current_time function
     * 
     * @param string $type Type of time (mysql, timestamp)
     * @param int|bool $gmt GMT offset
     * @return string|int Current time
     */
    function current_time(string $type, $gmt = 0) {
        switch ($type) {
            case 'mysql':
                return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            case 'timestamp':
                return $gmt ? time() : (time() + (get_option('gmt_offset', 0) * HOUR_IN_SECONDS));
            default:
                return $gmt ? time() : (time() + (get_option('gmt_offset', 0) * HOUR_IN_SECONDS));
        }
    }
}

if (!function_exists('get_option')) {
    /**
     * Mock get_option function
     * 
     * @param string $option Option name
     * @param mixed $default Default value
     * @return mixed Option value
     */
    function get_option(string $option, $default = false) {
        global $wp_option;
        
        if (!isset($wp_option)) {
            $wp_option = [];
        }
        
        return $wp_option[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    /**
     * Mock update_option function
     * 
     * @param string $option Option name
     * @param mixed $value Option value
     * @param string|bool $autoload Autoload option
     * @return bool True on success, false on failure
     */
    function update_option(string $option, $value, $autoload = null): bool {
        global $wp_option;
        
        if (!isset($wp_option)) {
            $wp_option = [];
        }
        
        $wp_option[$option] = $value;
        return true;
    }
}

if (!function_exists('wp_hash_password')) {
    /**
     * Mock wp_hash_password function
     * 
     * @param string $password Password to hash
     * @return string Hashed password
     */
    function wp_hash_password(string $password): string {
        // Security: Use secure password hashing (OWASP A02:2021 - Cryptographic Failures)
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
}

if (!function_exists('wp_generate_password')) {
    /**
     * Mock wp_generate_password function
     * 
     * @param int $length Password length
     * @param bool $special_chars Include special characters
     * @param bool $extra_special_chars Include extra special characters
     * @return string Generated password
     */
    function wp_generate_password(int $length = 12, bool $special_chars = true, bool $extra_special_chars = false): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        
        if ($extra_special_chars) {
            $chars .= '-_ []{}<>~`+=,.;:/?|';
        }
        
        $password = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $chars_length - 1)];
        }
        
        return $password;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    /**
     * Mock wp_strip_all_tags function
     * 
     * @param string $string String to strip tags from
     * @param bool $remove_breaks Remove line breaks
     * @return string Stripped string
     */
    function wp_strip_all_tags(string $string, bool $remove_breaks = false): string {
        $string = strip_tags($string);
        
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        
        return trim($string);
    }
}

if (!function_exists('wp_kses_post')) {
    /**
     * Mock wp_kses_post function
     * 
     * @param string $data Data to sanitize
     * @return string Sanitized data
     */
    function wp_kses_post(string $data): string {
        // Basic HTML sanitization for testing
        $allowed_tags = '<a><em><strong><cite><blockquote><code><ul><ol><li><dl><dt><dd><p><br><div><span><h1><h2><h3><h4><h5><h6><img>';
        return strip_tags($data, $allowed_tags);
    }
}

if (!function_exists('sanitize_title')) {
    /**
     * Mock sanitize_title function
     * 
     * @param string $title Title to sanitize
     * @param string $fallback_title Fallback title
     * @param string $context Context
     * @return string Sanitized title
     */
    function sanitize_title(string $title, string $fallback_title = '', string $context = 'save'): string {
        $title = strip_tags($title);
        $title = remove_accents($title);
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9\-_]/', '-', $title);
        $title = preg_replace('/-+/', '-', $title);
        $title = trim($title, '-');
        
        if (empty($title) && !empty($fallback_title)) {
            $title = sanitize_title($fallback_title);
        }
        
        return $title;
    }
}

if (!function_exists('remove_accents')) {
    /**
     * Mock remove_accents function
     * 
     * @param string $string String to remove accents from
     * @return string String without accents
     */
    function remove_accents(string $string): string {
        // Basic accent removal for testing
        $chars = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ý' => 'Y', 'ý' => 'y', 'ÿ' => 'y',
            'Ñ' => 'N', 'ñ' => 'n',
            'Ç' => 'C', 'ç' => 'c'
        ];
        
        return strtr($string, $chars);
    }
}

if (!function_exists('current_user_can')) {
    /**
     * Mock current_user_can function
     * 
     * @param string $capability Capability to check
     * @param mixed ...$args Additional arguments
     * @return bool True if user has capability, false otherwise
     */
    function current_user_can(string $capability, ...$args): bool {
        global $current_user;
        
        if (!isset($current_user)) {
            return false;
        }
        
        return isset($current_user->allcaps[$capability]) && $current_user->allcaps[$capability];
    }
}

if (!function_exists('user_can')) {
    /**
     * Mock user_can function
     * 
     * @param int $user_id User ID
     * @param string $capability Capability to check
     * @param mixed ...$args Additional arguments
     * @return bool True if user has capability, false otherwise
     */
    function user_can(int $user_id, string $capability, ...$args): bool {
        global $wp_users;
        
        if (!isset($wp_users[$user_id])) {
            return false;
        }
        
        $user = $wp_users[$user_id];
        return isset($user->allcaps[$capability]) && $user->allcaps[$capability];
    }
}

if (!function_exists('get_allowed_mime_types')) {
    /**
     * Mock get_allowed_mime_types function
     * 
     * @return array Allowed MIME types
     */
    function get_allowed_mime_types(): array {
        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'tiff|tif' => 'image/tiff',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'heic' => 'image/heic',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'pot|pps|ppt' => 'application/vnd.ms-powerpoint',
            'wri' => 'application/vnd.ms-write',
            'xla|xls|xlt|xlw' => 'application/vnd.ms-excel',
            'mdb' => 'application/vnd.ms-access',
            'mpp' => 'application/vnd.ms-project',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'sldm' => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
            'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
            'oxps' => 'application/oxps',
            'xps' => 'application/vnd.ms-xpsdocument',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            'wp|wpd' => 'application/wordperfect',
            'key' => 'application/vnd.apple.keynote',
            'numbers' => 'application/vnd.apple.numbers',
            'pages' => 'application/vnd.apple.pages'
        ];
    }
}

// Define WordPress constants if not already defined
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}

if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 2629746);
}

if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 31556952);
}
