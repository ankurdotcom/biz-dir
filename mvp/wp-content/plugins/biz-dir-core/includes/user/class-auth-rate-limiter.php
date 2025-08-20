<?php
namespace BizDir\Core\User;

/**
 * Auth Rate Limiter Class
 */
class Auth_Rate_Limiter {
    const ATTEMPT_EXPIRY = 3600; // 1 hour
    const MAX_ATTEMPTS = 5;
    
    /**
     * Check and update rate limit
     *
     * @param string $key Identifier (username or IP)
     * @return bool True if allowed, false if rate limited
     */
    public function check_rate_limit($key) {
        $attempts = get_transient('auth_attempts_' . md5($key));
        if ($attempts === false) {
            set_transient('auth_attempts_' . md5($key), 1, self::ATTEMPT_EXPIRY);
            return true;
        }
        
        if ($attempts >= self::MAX_ATTEMPTS) {
            return false;
        }
        
        set_transient('auth_attempts_' . md5($key), $attempts + 1, self::ATTEMPT_EXPIRY);
        return true;
    }
    
    /**
     * Reset rate limit counter
     *
     * @param string $key Identifier to reset
     */
    public function reset_rate_limit($key) {
        delete_transient('auth_attempts_' . md5($key));
    }
}
