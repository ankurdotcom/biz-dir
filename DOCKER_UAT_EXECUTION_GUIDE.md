# 🐳 Docker Production UAT Execution Guide
## BizDir Business Directory Platform - Container-Based Testing

**Version:** 1.0 - Docker Production Setup  
**Date:** August 23, 2025  
**Purpose:** Comprehensive UAT guide for Docker-based production environment

---

## 🎯 **DOCKER PRODUCTION UAT OVERVIEW**

### **Infrastructure Benefits**
✅ **Production Similarity**: 100% production-equivalent environment  
✅ **One-Command Deployment**: Instant setup with `./deploy.sh deploy`  
✅ **Multi-Service Architecture**: Web, App, Database, Cache, Monitoring  
✅ **Enterprise Features**: SSL, monitoring, backups, health checks  
✅ **Scalability Ready**: Horizontal scaling preparation built-in  

### **Testing Scope**
- **Container Orchestration**: Service dependencies and health monitoring
- **Production Performance**: Real-world load and response testing  
- **Security Validation**: SSL, headers, rate limiting, container security
- **Business Logic**: Full WordPress functionality in production environment
- **Monitoring & Alerting**: Prometheus metrics and Grafana dashboards

---

## 🚀 **DOCKER UAT QUICK START**

### **Step 1: Environment Preparation**
```bash
# Navigate to project directory
cd /home/ankur/workspace/biz-dir

# Copy environment configuration
cp .env.example .env

# Edit configuration (IMPORTANT: Change passwords!)
nano .env
```

### **Step 2: Deploy Production Environment**
```bash
# Deploy core services (recommended for UAT)
./deploy.sh deploy

# OR deploy with full monitoring stack
./deploy.sh deploy full
```

### **Step 3: Verify Deployment**
```bash
# Check all services status
./deploy.sh status

# View service logs
./deploy.sh logs

# Test connectivity
curl -I http://localhost
```

### **Step 4: Access UAT Environment**
- **Main Application**: http://localhost
- **WordPress Admin**: http://localhost/wp-admin  
- **Grafana Monitoring**: http://localhost:3000 (if full deployment)
- **Prometheus Metrics**: http://localhost:9090 (if full deployment)

---

## 📋 **DOCKER UAT TESTING PHASES**

### **🔧 Phase 1: Container Infrastructure Validation**

#### **Service Health Verification**
```bash
# Check all containers are running
docker-compose ps

# Verify health status
./deploy.sh status

# Test database connectivity
docker-compose exec db mysql -u wordpress -p

# Test Redis cache
docker-compose exec redis redis-cli ping
```

#### **Network & Communication Testing**
```bash
# Test inter-container communication
docker-compose exec php ping db
docker-compose exec php ping redis

# Test web server response
curl -v http://localhost

# Test SSL configuration (if enabled)
curl -I https://localhost
```

#### **Volume & Data Persistence**
```bash
# Verify data volumes
docker volume ls
ls -la data/

# Test data persistence
./deploy.sh restart
# Verify data still exists after restart
```

### **🌐 Phase 2: Web Application Testing**

#### **WordPress Functionality**
- [ ] **Installation Process**: Verify automatic WordPress setup
- [ ] **Admin Access**: Test wp-admin login functionality
- [ ] **Plugin Activation**: Verify BizDir plugins load correctly
- [ ] **Theme Application**: Test theme rendering and responsiveness
- [ ] **Database Connection**: Confirm WordPress-MySQL integration

#### **Performance Validation**
```bash
# Monitor resource usage
docker stats

# Test page load times
curl -w "%{time_total}\n" -o /dev/null -s http://localhost

# Check OPCache status
docker-compose exec php php -i | grep opcache
```

#### **Cache Layer Testing**
```bash
# Verify Redis integration
docker-compose exec php php -r "echo extension_loaded('redis') ? 'Redis loaded' : 'Redis not loaded';"

# Test cache operations
docker-compose exec redis redis-cli monitor
# (Perform website actions and observe cache activity)
```

### **🛡️ Phase 3: Security & Production Readiness**

#### **Security Headers Validation**
```bash
# Test security headers
curl -I http://localhost | grep -E "(X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security)"

# Test rate limiting
for i in {1..10}; do curl -s -o /dev/null -w "%{http_code}\n" http://localhost/wp-login.php; done
```

#### **SSL/TLS Configuration** (if enabled)
```bash
# Test SSL certificate
openssl s_client -connect localhost:443 -servername localhost

# Verify HTTPS redirect
curl -I http://localhost
```

#### **File Security Testing**
```bash
# Test direct PHP file access protection
curl -I http://localhost/wp-config.php
curl -I http://localhost/wp-content/plugins/biz-dir-core/biz-dir-core.php

# Test file upload restrictions
# (Upload test files through WordPress admin)
```

### **📊 Phase 4: Business Logic & User Workflows**

#### **BizDir Core Functionality**
- [ ] **Business Listing Creation**: Create new business entries
- [ ] **Review System**: Submit and manage reviews  
- [ ] **Search & Filter**: Test search functionality
- [ ] **User Registration**: New user signup process
- [ ] **Admin Moderation**: Content approval workflows

#### **WordPress Integration**
- [ ] **User Roles**: Test different user permission levels
- [ ] **Content Management**: Post creation and editing
- [ ] **Media Upload**: Image and file upload functionality
- [ ] **Plugin Interactions**: BizDir plugin compatibility

### **📈 Phase 5: Monitoring & Maintenance**

#### **Monitoring Stack Testing** (if full deployment)
```bash
# Access Grafana dashboard
open http://localhost:3000
# Login: admin / admin123! (or configured password)

# Check Prometheus targets
open http://localhost:9090/targets

# View metrics
docker-compose exec prometheus cat /etc/prometheus/prometheus.yml
```

#### **Backup & Recovery Testing**
```bash
# Create backup
./deploy.sh backup

# List backups
ls -la backups/

# Test restore process (use with caution in UAT)
# ./deploy.sh restore backups/backup_YYYYMMDD_HHMMSS
```

---

## 🎯 **UAT SUCCESS CRITERIA**

### **Container Infrastructure (Must Pass)**
- [ ] All containers start successfully and remain healthy
- [ ] Service dependencies work correctly (PHP→DB, PHP→Redis)
- [ ] Data persistence works after container restarts
- [ ] Network communication between containers functional
- [ ] Resource usage within acceptable limits

### **Application Functionality (Must Pass)**
- [ ] WordPress installation completes automatically
- [ ] BizDir plugins activate without errors
- [ ] Core business directory functionality works
- [ ] User authentication and authorization functional
- [ ] Database operations (CRUD) work correctly

### **Performance Standards (Should Pass)**
- [ ] Page load times under 2 seconds (production-grade)
- [ ] Container startup time under 60 seconds
- [ ] Memory usage stable under normal load
- [ ] Cache hit ratio above 80% (Redis)
- [ ] Database query response under 100ms average

### **Security Validation (Must Pass)**
- [ ] Security headers properly configured
- [ ] Rate limiting functional on sensitive endpoints
- [ ] File access restrictions working
- [ ] SSL/TLS configuration (if enabled) secure
- [ ] Container security (no privileged access)

### **Production Readiness (Should Pass)**
- [ ] Monitoring system operational (if enabled)
- [ ] Backup system functional
- [ ] Log aggregation working
- [ ] Health checks responding correctly
- [ ] Graceful shutdown and restart capability

---

## 🛠️ **DOCKER UAT COMMANDS REFERENCE**

### **Deployment Management**
```bash
# Deploy environment
./deploy.sh deploy                    # Core services
./deploy.sh deploy full              # All services including monitoring

# Service management
./deploy.sh start                    # Start all services
./deploy.sh stop                     # Stop all services  
./deploy.sh restart                  # Restart all services
./deploy.sh status                   # Check service status
```

### **Troubleshooting & Debugging**
```bash
# View logs
./deploy.sh logs                     # All services
./deploy.sh logs nginx               # Specific service
./deploy.sh logs php --tail 100     # Last 100 lines

# Container access
./deploy.sh shell php                # Access PHP container
./deploy.sh shell db                 # Access database container

# Service inspection
docker-compose ps                    # Container status
docker-compose top                   # Running processes
docker stats                        # Resource usage
```

### **Data Management**
```bash
# Backup operations
./deploy.sh backup                   # Create backup
ls backups/                         # List backups

# Volume management
docker volume ls                     # List data volumes
docker volume inspect bizdir_db_data # Inspect specific volume
```

---

## 🚨 **TROUBLESHOOTING GUIDE**

### **Common Issues & Solutions**

#### **Issue: Containers won't start**
```bash
# Check Docker daemon
sudo systemctl status docker

# Check compose file syntax
docker-compose config

# View container logs
docker-compose logs [service-name]

# Rebuild containers
docker-compose build --no-cache
```

#### **Issue: Database connection errors**
```bash
# Check MySQL container
./deploy.sh logs db

# Test database connectivity
docker-compose exec db mysql -u root -p

# Verify environment variables
docker-compose exec php env | grep DB_
```

#### **Issue: Performance problems**
```bash
# Monitor resource usage
docker stats

# Check container limits
docker-compose exec php cat /proc/meminfo

# Review PHP-FPM configuration
docker-compose exec php cat /usr/local/etc/php-fpm.d/www.conf
```

#### **Issue: Network connectivity problems**
```bash
# Check network configuration
docker network ls
docker network inspect bizdir_default

# Test inter-container communication
docker-compose exec php ping db
docker-compose exec php ping redis
```

---

## 📊 **UAT REPORTING & METRICS**

### **Test Results Documentation**
- **Container Health**: Service status and uptime metrics
- **Performance Metrics**: Response times, resource usage, throughput
- **Security Validation**: Security scan results, penetration test findings
- **Functional Testing**: Business logic validation results
- **User Experience**: End-to-end workflow testing outcomes

### **Production Readiness Report**
Create comprehensive report covering:
1. **Infrastructure Validation**: Container orchestration results
2. **Application Testing**: WordPress and BizDir functionality
3. **Performance Analysis**: Load testing and optimization results  
4. **Security Assessment**: Vulnerability scanning and hardening validation
5. **Operational Readiness**: Monitoring, backup, and maintenance procedures

---

## 🎉 **UAT COMPLETION CRITERIA**

### **Go-Live Readiness Checklist**
- [ ] **100% Container Health**: All services operational
- [ ] **Security Validation**: All security tests passed
- [ ] **Performance Standards**: All benchmarks met
- [ ] **Business Logic**: Core functionality validated
- [ ] **User Acceptance**: Stakeholder sign-off completed
- [ ] **Documentation**: Production runbooks completed
- [ ] **Backup & Recovery**: Disaster recovery tested
- [ ] **Monitoring**: Operational visibility confirmed

### **Final Sign-Off Requirements**
- **Technical Lead**: Infrastructure and application validation ✅
- **Security Team**: Security compliance confirmation ✅  
- **Business Owner**: Functional requirements acceptance ✅
- **DevOps Team**: Operational readiness verification ✅

---

**🚀 Docker Production UAT Environment Ready for Testing! 🚀**

**Report Generated**: August 23, 2025  
**Environment**: Docker Compose Production Setup  
**Status**: Ready for comprehensive UAT execution  
**Next Steps**: Execute testing phases and document results

---

*For additional support, refer to docker/README.md or run `./deploy.sh` without arguments for help.*
