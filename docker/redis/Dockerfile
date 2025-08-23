FROM redis:7.2-alpine

# Copy custom configuration
COPY redis.conf /usr/local/etc/redis/redis.conf

# Create necessary directories and set permissions
RUN mkdir -p /var/log/redis && \
    mkdir -p /var/run/redis && \
    chown -R redis:redis /var/log/redis /var/run/redis /data

# Switch to redis user
USER redis

# Expose port
EXPOSE 6379

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD redis-cli ping || exit 1

# Start Redis with custom config
CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]
