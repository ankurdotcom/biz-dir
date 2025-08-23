FROM nginx:1.24-alpine

# Install dependencies
RUN apk add --no-cache \
    certbot \
    certbot-nginx \
    openssl

# Copy configurations
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf

# Create necessary directories
RUN mkdir -p /var/www/html && \
    mkdir -p /etc/letsencrypt && \
    mkdir -p /var/lib/letsencrypt && \
    mkdir -p /var/log/letsencrypt

# Set permissions
RUN chown -R nginx:nginx /var/www/html

# Create a simple index file for health check
RUN echo '<?php echo "Nginx is running!"; ?>' > /var/www/html/health.php

# Expose ports
EXPOSE 80 443

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
