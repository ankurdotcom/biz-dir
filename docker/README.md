# Business Directory - Docker Production Setup

This directory contains a complete production-ready Docker containerization setup for the Business Directory application.

## 🚀 Quick Start

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit configuration:**
   ```bash
   nano .env
   ```

3. **Deploy application:**
   ```bash
   ./deploy.sh deploy
   ```

4. **Access your application:**
   - WordPress: http://localhost
   - Admin Panel: http://localhost/wp-admin

## 📁 Directory Structure

```
docker/
├── nginx/          # Nginx web server configuration
├── php/            # PHP-FPM application server
├── mysql/          # MySQL database configuration
└── redis/          # Redis cache configuration

data/               # Persistent data storage
logs/               # Application logs
backups/            # Backup storage
ssl/                # SSL certificates
```

## 🏗️ Architecture

The setup includes the following services:

### Core Services
- **Nginx**: Web server with SSL support and security headers
- **PHP-FPM**: Application server with WordPress optimizations
- **MySQL 8.0**: Database with production-tuned configuration
- **Redis**: Caching layer for improved performance

### Optional Services
- **Elasticsearch**: Full-text search capabilities
- **Prometheus**: Metrics collection and monitoring
- **Grafana**: Visualization and alerting dashboard
- **Backup Service**: Automated backup solution

## 🛠️ Deployment Commands

### Basic Deployment
```bash
# Deploy core services only
./deploy.sh deploy

# Deploy with all optional services
./deploy.sh deploy full
```

### Management Commands
```bash
# Start services
./deploy.sh start

# Stop services
./deploy.sh stop

# Restart services
./deploy.sh restart

# View logs
./deploy.sh logs [service-name]

# Check status
./deploy.sh status

# Access service shell
./deploy.sh shell [service-name]
```

### Backup & Restore
```bash
# Create backup
./deploy.sh backup

# Restore from backup
./deploy.sh restore backups/backup_20231201_120000
```

## ⚙️ Configuration

### Environment Variables

Key variables in `.env`:

```bash
# WordPress Settings
WP_URL=http://localhost
WP_ADMIN_USER=admin
WP_ADMIN_PASSWORD=changeme123!

# Database Settings
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=wordpress123!
MYSQL_ROOT_PASSWORD=rootpassword123!

# Security Settings
WP_DISALLOW_FILE_EDIT=true
WP_AUTO_UPDATE_CORE=minor
```

### Service Ports

- **HTTP**: 80
- **HTTPS**: 443
- **MySQL**: 3306
- **Redis**: 6379
- **Elasticsearch**: 9200
- **Prometheus**: 9090
- **Grafana**: 3000

## 🔒 Security Features

- **Security Headers**: HSTS, CSP, X-Frame-Options, etc.
- **Rate Limiting**: Protection against brute force attacks
- **File Upload Restrictions**: Secure file handling
- **SSL/TLS Support**: Ready for HTTPS with Let's Encrypt
- **Database Security**: Non-root MySQL user with limited privileges
- **PHP Security**: Disabled dangerous functions and hardened configuration

## 📊 Monitoring

When deployed with `full` option:

- **Grafana Dashboard**: http://localhost:3000
  - Username: admin
  - Password: admin123! (configurable in .env)

- **Prometheus Metrics**: http://localhost:9090

## 💾 Data Persistence

All data is stored in Docker volumes:

- **WordPress files**: `./data/wordpress`
- **MySQL data**: `./data/mysql`
- **Redis data**: `./data/redis`
- **Logs**: `./logs/`
- **Backups**: `./backups/`

## 🔧 Customization

### Adding PHP Extensions

Edit `docker/php/Dockerfile`:

```dockerfile
RUN docker-php-ext-install extension_name
```

### Nginx Configuration

Modify `docker/nginx/default.conf` for custom rules.

### Database Tuning

Update `docker/mysql/my.cnf` for performance tuning.

### Redis Configuration

Adjust `docker/redis/redis.conf` for caching strategy.

## 🚨 Troubleshooting

### Check Service Health
```bash
docker-compose ps
```

### View Service Logs
```bash
./deploy.sh logs nginx
./deploy.sh logs php
./deploy.sh logs db
```

### Common Issues

1. **Permission Errors**:
   ```bash
   sudo chown -R www-data:www-data data/
   ```

2. **Database Connection**:
   - Check if MySQL is running
   - Verify credentials in .env
   - Wait for services to fully start

3. **Memory Issues**:
   - Increase PHP memory limit in .env
   - Adjust MySQL buffer pool size

### Performance Optimization

1. **Enable OPCache**: Already configured in PHP
2. **Redis Caching**: Install Redis Object Cache plugin
3. **Database Optimization**: Regular optimization and indexing
4. **CDN**: Configure external CDN for static assets

## 📝 Maintenance

### Regular Tasks

1. **Update WordPress**: Core and plugins are auto-updated
2. **Monitor Logs**: Check for errors and unusual activity
3. **Backup Data**: Automated backups run daily
4. **Security Updates**: Keep Docker images updated

### Scaling

For high-traffic scenarios:

1. **Load Balancing**: Add multiple PHP-FPM containers
2. **Database Replication**: MySQL master-slave setup
3. **Redis Clustering**: Multiple Redis instances
4. **CDN Integration**: External content delivery

## 🆘 Support

For issues and questions:

1. Check the logs: `./deploy.sh logs`
2. Verify configuration: `./deploy.sh status`
3. Review documentation and troubleshooting guide
4. Check WordPress and plugin documentation

## 📜 License

This Docker setup is part of the Business Directory project and follows the same licensing terms.
