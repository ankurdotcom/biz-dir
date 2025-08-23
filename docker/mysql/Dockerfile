FROM mysql:8.0

# Copy custom configuration
COPY my.cnf /etc/mysql/conf.d/

# Create necessary directories
RUN mkdir -p /var/log/mysql && \
    chown -R mysql:mysql /var/log/mysql

# Set environment variables
ENV MYSQL_ROOT_PASSWORD=rootpassword
ENV MYSQL_DATABASE=wordpress
ENV MYSQL_USER=wordpress
ENV MYSQL_PASSWORD=wordpress

# Expose port
EXPOSE 3306

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD mysqladmin ping -h localhost || exit 1
