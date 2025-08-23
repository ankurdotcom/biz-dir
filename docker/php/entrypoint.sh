#!/bin/bash
set -e

# Function to wait for service
wait_for_service() {
    local host=$1
    local port=$2
    local service_name=$3
    
    echo "Waiting for $service_name to be ready..."
    while ! nc -z "$host" "$port"; do
        echo "Waiting for $service_name at $host:$port..."
        sleep 2
    done
    echo "$service_name is ready!"
}

# Function to check if WordPress is installed
check_wordpress_installed() {
    if wp core is-installed --path=/var/www/html --allow-root 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Wait for database
wait_for_service "${DB_HOST:-db}" "${DB_PORT:-3306}" "MySQL"

# Wait for Redis
wait_for_service "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}" "Redis"

echo "Starting WordPress setup..."

# Navigate to web root
cd /var/www/html

# Download WordPress if not exists
if [ ! -f wp-config.php ]; then
    echo "WordPress not found. Downloading..."
    wp core download --allow-root --force
fi

# Create wp-config.php if it doesn't exist
if [ ! -f wp-config.php ]; then
    echo "Creating wp-config.php..."
    wp config create \
        --dbname="${DB_NAME:-wordpress}" \
        --dbuser="${DB_USER:-wordpress}" \
        --dbpass="${DB_PASSWORD:-wordpress}" \
        --dbhost="${DB_HOST:-db}" \
        --dbprefix="${DB_PREFIX:-wp_}" \
        --allow-root

    # Add Redis configuration
    cat >> wp-config.php << 'EOF'

// Redis Configuration
define('WP_REDIS_HOST', getenv('REDIS_HOST') ?: 'redis');
define('WP_REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);

// Security configurations
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
define('WP_AUTO_UPDATE_CORE', 'minor');

// Memory and performance
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Enable debugging in development
if (getenv('WP_DEBUG') === 'true') {
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', false);
    define('SCRIPT_DEBUG', true);
} else {
    define('WP_DEBUG', false);
}

// SSL Configuration
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
EOF
fi

# Install WordPress if not already installed
if ! check_wordpress_installed; then
    echo "Installing WordPress..."
    wp core install \
        --url="${WP_URL:-http://localhost}" \
        --title="${WP_TITLE:-WordPress Site}" \
        --admin_user="${WP_ADMIN_USER:-admin}" \
        --admin_password="${WP_ADMIN_PASSWORD:-admin}" \
        --admin_email="${WP_ADMIN_EMAIL:-admin@example.com}" \
        --allow-root
        
    echo "WordPress installed successfully!"
else
    echo "WordPress is already installed."
fi

# Install and activate plugins if specified
if [ -n "$WP_PLUGINS" ]; then
    echo "Installing plugins: $WP_PLUGINS"
    for plugin in $WP_PLUGINS; do
        wp plugin install "$plugin" --activate --allow-root
    done
fi

# Install and activate theme if specified
if [ -n "$WP_THEME" ]; then
    echo "Installing theme: $WP_THEME"
    wp theme install "$WP_THEME" --activate --allow-root
fi

# Set proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;
chmod 600 wp-config.php

# Update WordPress and plugins
echo "Updating WordPress core and plugins..."
wp core update --allow-root
wp plugin update --all --allow-root

# Flush rewrite rules
wp rewrite flush --allow-root

echo "WordPress setup completed!"

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
