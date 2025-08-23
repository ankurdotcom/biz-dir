<?php
/**
 * Mock WordPress Hooks System
 * 
 * Industry-standard implementation of WordPress hooks (actions/filters) system
 * Following OWASP A06:2021 - Vulnerable Components mitigation
 * 
 * @package BizDir
 * @subpackage Tests\Mocks
 * @since 1.0.0
 */

if (!class_exists('MockWordPressHooks')) {
    /**
     * WordPress Hooks Mock System
     * 
     * Provides complete compatibility with WordPress hooks system
     * while maintaining security and audit capabilities
     */
    class MockWordPressHooks {
        
        /**
         * Registered actions
         * 
         * @var array
         */
        private static $actions = [];
        
        /**
         * Registered filters
         * 
         * @var array
         */
        private static $filters = [];
        
        /**
         * Called actions log
         * 
         * @var array
         */
        private static $called_actions = [];
        
        /**
         * Applied filters log
         * 
         * @var array
         */
        private static $applied_filters = [];
        
        /**
         * Security: Hook execution audit log
         * 
         * @var array
         */
        private static $hook_audit_log = [];
        
        /**
         * Add an action hook
         * 
         * @param string $hook_name Action name
         * @param callable $callback Callback function
         * @param int $priority Priority (default 10)
         * @param int $accepted_args Number of accepted arguments (default 1)
         * @return true Always returns true
         */
        public static function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
            // Security: Validate hook name (OWASP A03:2021 - Injection)
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\/\-]*$/', $hook_name)) {
                throw new InvalidArgumentException('Invalid hook name format');
            }
            
            if (!isset(self::$actions[$hook_name])) {
                self::$actions[$hook_name] = [];
            }
            
            if (!isset(self::$actions[$hook_name][$priority])) {
                self::$actions[$hook_name][$priority] = [];
            }
            
            $callback_id = self::generate_callback_id($callback);
            
            self::$actions[$hook_name][$priority][$callback_id] = [
                'callback' => $callback,
                'accepted_args' => $accepted_args,
                'added_at' => microtime(true)
            ];
            
            // Security: Audit log entry
            self::log_hook_event('action_added', [
                'hook_name' => $hook_name,
                'priority' => $priority,
                'callback_id' => $callback_id,
                'accepted_args' => $accepted_args
            ]);
            
            return true;
        }
        
        /**
         * Add a filter hook
         * 
         * @param string $hook_name Filter name
         * @param callable $callback Callback function
         * @param int $priority Priority (default 10)
         * @param int $accepted_args Number of accepted arguments (default 1)
         * @return true Always returns true
         */
        public static function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
            // Security: Validate hook name
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\/\-]*$/', $hook_name)) {
                throw new InvalidArgumentException('Invalid hook name format');
            }
            
            if (!isset(self::$filters[$hook_name])) {
                self::$filters[$hook_name] = [];
            }
            
            if (!isset(self::$filters[$hook_name][$priority])) {
                self::$filters[$hook_name][$priority] = [];
            }
            
            $callback_id = self::generate_callback_id($callback);
            
            self::$filters[$hook_name][$priority][$callback_id] = [
                'callback' => $callback,
                'accepted_args' => $accepted_args,
                'added_at' => microtime(true)
            ];
            
            // Security: Audit log entry
            self::log_hook_event('filter_added', [
                'hook_name' => $hook_name,
                'priority' => $priority,
                'callback_id' => $callback_id,
                'accepted_args' => $accepted_args
            ]);
            
            return true;
        }
        
        /**
         * Execute an action
         * 
         * @param string $hook_name Action name
         * @param mixed ...$args Arguments to pass to callbacks
         * @return void
         */
        public static function do_action(string $hook_name, ...$args): void {
            // Log action call
            self::$called_actions[] = [
                'hook_name' => $hook_name,
                'args' => $args,
                'timestamp' => microtime(true)
            ];
            
            if (!isset(self::$actions[$hook_name])) {
                return;
            }
            
            // Sort by priority
            ksort(self::$actions[$hook_name]);
            
            foreach (self::$actions[$hook_name] as $priority => $callbacks) {
                foreach ($callbacks as $callback_id => $callback_data) {
                    $callback = $callback_data['callback'];
                    $accepted_args = $callback_data['accepted_args'];
                    
                    // Limit arguments based on accepted_args
                    $call_args = array_slice($args, 0, $accepted_args);
                    
                    try {
                        // Security: Execute in controlled environment
                        call_user_func_array($callback, $call_args);
                        
                        // Security: Log successful execution
                        self::log_hook_event('action_executed', [
                            'hook_name' => $hook_name,
                            'callback_id' => $callback_id,
                            'priority' => $priority,
                            'args_count' => count($call_args)
                        ]);
                        
                    } catch (Throwable $e) {
                        // Security: Log execution error (OWASP A09:2021 - Security Logging)
                        self::log_hook_event('action_error', [
                            'hook_name' => $hook_name,
                            'callback_id' => $callback_id,
                            'error_message' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine()
                        ]);
                        
                        // Re-throw in test environment for debugging
                        if (defined('BIZ_DIR_TESTING_MODE') && BIZ_DIR_TESTING_MODE) {
                            throw $e;
                        }
                    }
                }
            }
        }
        
        /**
         * Apply filters
         * 
         * @param string $hook_name Filter name
         * @param mixed $value Value to filter
         * @param mixed ...$args Additional arguments
         * @return mixed Filtered value
         */
        public static function apply_filters(string $hook_name, $value, ...$args): mixed {
            // Log filter application
            self::$applied_filters[] = [
                'hook_name' => $hook_name,
                'original_value' => $value,
                'args' => $args,
                'timestamp' => microtime(true)
            ];
            
            if (!isset(self::$filters[$hook_name])) {
                return $value;
            }
            
            // Sort by priority
            ksort(self::$filters[$hook_name]);
            
            $filtered_value = $value;
            
            foreach (self::$filters[$hook_name] as $priority => $callbacks) {
                foreach ($callbacks as $callback_id => $callback_data) {
                    $callback = $callback_data['callback'];
                    $accepted_args = $callback_data['accepted_args'];
                    
                    // Prepare arguments: filtered value + additional args
                    $call_args = array_merge([$filtered_value], array_slice($args, 0, $accepted_args - 1));
                    
                    try {
                        // Security: Execute in controlled environment
                        $filtered_value = call_user_func_array($callback, $call_args);
                        
                        // Security: Log successful execution
                        self::log_hook_event('filter_applied', [
                            'hook_name' => $hook_name,
                            'callback_id' => $callback_id,
                            'priority' => $priority,
                            'value_changed' => $filtered_value !== $value
                        ]);
                        
                    } catch (Throwable $e) {
                        // Security: Log execution error
                        self::log_hook_event('filter_error', [
                            'hook_name' => $hook_name,
                            'callback_id' => $callback_id,
                            'error_message' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine()
                        ]);
                        
                        // Re-throw in test environment
                        if (defined('BIZ_DIR_TESTING_MODE') && BIZ_DIR_TESTING_MODE) {
                            throw $e;
                        }
                        
                        // Continue with original value on error
                        continue;
                    }
                }
            }
            
            return $filtered_value;
        }
        
        /**
         * Remove an action
         * 
         * @param string $hook_name Action name
         * @param callable $callback Callback function
         * @param int $priority Priority
         * @return bool True if removed, false otherwise
         */
        public static function remove_action(string $hook_name, callable $callback, int $priority = 10): bool {
            if (!isset(self::$actions[$hook_name][$priority])) {
                return false;
            }
            
            $callback_id = self::generate_callback_id($callback);
            
            if (isset(self::$actions[$hook_name][$priority][$callback_id])) {
                unset(self::$actions[$hook_name][$priority][$callback_id]);
                
                // Clean up empty priority arrays
                if (empty(self::$actions[$hook_name][$priority])) {
                    unset(self::$actions[$hook_name][$priority]);
                }
                
                // Clean up empty hook arrays
                if (empty(self::$actions[$hook_name])) {
                    unset(self::$actions[$hook_name]);
                }
                
                self::log_hook_event('action_removed', [
                    'hook_name' => $hook_name,
                    'callback_id' => $callback_id,
                    'priority' => $priority
                ]);
                
                return true;
            }
            
            return false;
        }
        
        /**
         * Remove a filter
         * 
         * @param string $hook_name Filter name
         * @param callable $callback Callback function
         * @param int $priority Priority
         * @return bool True if removed, false otherwise
         */
        public static function remove_filter(string $hook_name, callable $callback, int $priority = 10): bool {
            if (!isset(self::$filters[$hook_name][$priority])) {
                return false;
            }
            
            $callback_id = self::generate_callback_id($callback);
            
            if (isset(self::$filters[$hook_name][$priority][$callback_id])) {
                unset(self::$filters[$hook_name][$priority][$callback_id]);
                
                // Clean up empty priority arrays
                if (empty(self::$filters[$hook_name][$priority])) {
                    unset(self::$filters[$hook_name][$priority]);
                }
                
                // Clean up empty hook arrays
                if (empty(self::$filters[$hook_name])) {
                    unset(self::$filters[$hook_name]);
                }
                
                self::log_hook_event('filter_removed', [
                    'hook_name' => $hook_name,
                    'callback_id' => $callback_id,
                    'priority' => $priority
                ]);
                
                return true;
            }
            
            return false;
        }
        
        /**
         * Check if action was called
         * 
         * @param string $hook_name Action name
         * @return bool True if called, false otherwise
         */
        public static function was_action_called(string $hook_name): bool {
            foreach (self::$called_actions as $action) {
                if ($action['hook_name'] === $hook_name) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         * Check if filter was applied
         * 
         * @param string $hook_name Filter name
         * @return bool True if applied, false otherwise
         */
        public static function was_filter_applied(string $hook_name): bool {
            foreach (self::$applied_filters as $filter) {
                if ($filter['hook_name'] === $hook_name) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         * Get action call count
         * 
         * @param string $hook_name Action name
         * @return int Number of times called
         */
        public static function get_action_call_count(string $hook_name): int {
            $count = 0;
            foreach (self::$called_actions as $action) {
                if ($action['hook_name'] === $hook_name) {
                    $count++;
                }
            }
            return $count;
        }
        
        /**
         * Get filter application count
         * 
         * @param string $hook_name Filter name
         * @return int Number of times applied
         */
        public static function get_filter_application_count(string $hook_name): int {
            $count = 0;
            foreach (self::$applied_filters as $filter) {
                if ($filter['hook_name'] === $filter_name) {
                    $count++;
                }
            }
            return $count;
        }
        
        /**
         * Reset all hooks and logs
         * 
         * @return void
         */
        public static function reset(): void {
            self::$actions = [];
            self::$filters = [];
            self::$called_actions = [];
            self::$applied_filters = [];
            self::$hook_audit_log = [];
        }
        
        /**
         * Generate unique callback ID
         * 
         * @param callable $callback Callback function
         * @return string Unique callback ID
         */
        private static function generate_callback_id(callable $callback): string {
            if (is_string($callback)) {
                return $callback;
            }
            
            if (is_array($callback)) {
                if (is_object($callback[0])) {
                    return spl_object_id($callback[0]) . '::' . $callback[1];
                }
                return $callback[0] . '::' . $callback[1];
            }
            
            if ($callback instanceof Closure) {
                return 'closure_' . spl_object_id($callback);
            }
            
            if (is_object($callback)) {
                return 'object_' . spl_object_id($callback);
            }
            
            return 'unknown_' . md5(serialize($callback));
        }
        
        /**
         * Log hook event for security audit
         * 
         * @param string $event_type Event type
         * @param array $data Event data
         * @return void
         */
        private static function log_hook_event(string $event_type, array $data): void {
            self::$hook_audit_log[] = [
                'event_type' => $event_type,
                'data' => $data,
                'timestamp' => microtime(true),
                'memory_usage' => memory_get_usage(true)
            ];
        }
        
        /**
         * Get hook audit log
         * 
         * @return array Hook audit log
         */
        public static function get_hook_audit_log(): array {
            return self::$hook_audit_log;
        }
        
        /**
         * Get called actions log
         * 
         * @return array Called actions log
         */
        public static function get_called_actions(): array {
            return self::$called_actions;
        }
        
        /**
         * Get applied filters log
         * 
         * @return array Applied filters log
         */
        public static function get_applied_filters(): array {
            return self::$applied_filters;
        }
    }
}
