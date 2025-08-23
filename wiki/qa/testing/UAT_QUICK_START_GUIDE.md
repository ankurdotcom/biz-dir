---
title: UAT QUICK START GUIDE
description: Auto-synced from project documentation
published: true
date: 2025-08-24T00:25:59+05:30
tags: [testing, auto-sync, qa]
editor: markdown
dateCreated: 2025-08-24T00:25:59+05:30
---

# BizDir Pre-Production UAT Quick Start Guide
## User Acceptance Testing Setup and Execution

**Version:** 2.0 - Docker Production Setup  
**Date:** August 23, 2025  
**Purpose:** Quick setup guide for Docker-based UAT and production testing

**🔥 NEW: Production Docker Infrastructure Available**

---

## 🚀 QUICK START (2 Options Available)

### 🔥 **OPTION A: Production Docker Setup (Recommended)**
```bash
# Navigate to project directory
cd /home/ankur/workspace/biz-dir

# Copy and configure environment
cp .env.example .env
nano .env  # Edit your configuration

# Deploy production-grade environment
./deploy.sh deploy

# Access application
open http://localhost
```

### 🛠️ **OPTION B: Lightweight UAT Setup**
```bash
# Navigate to project directory  
cd /home/ankur/workspace/biz-dir

# Run lightweight UAT environment setup
./setup-lightweight-uat.sh

# Execute automated UAT validation tests
./run-uat-tests.sh
```

### **Access URLs**
- **Docker Setup**: http://localhost (WordPress), http://localhost/wp-admin (Admin)
- **Lightweight Setup**: http://localhost:8080 (UAT Environment)

### **Login Credentials**
```
Docker Setup (Configure in .env):
- Username: admin (default, configurable)
- Password: changeme123! (CHANGE THIS)

Lightweight Setup:
- Username: uatadmin  
- Password: UATAdmin@2025
```

---

## 📋 UAT TESTING CHECKLIST

### 🔥 **Phase 0: Infrastructure Setup (NEW)**
- [ ] **Docker Environment**: Run `./deploy.sh deploy` for production setup
- [ ] **Service Health**: Verify all containers running with `./deploy.sh status`
- [ ] **Database Connection**: Confirm MySQL container accessible
- [ ] **Cache Layer**: Verify Redis container operational
- [ ] **Web Server**: Test Nginx container with SSL support
- [ ] **Monitoring**: Access Grafana dashboard (if using full deployment)

### Phase 1: Environment Validation (Day 1)
- [ ] **Environment Setup**: Choose Docker (`./deploy.sh deploy`) or lightweight UAT
- [ ] **Basic Tests**: Run `./run-uat-tests.sh` (for lightweight) or container health checks
- [ ] **Access Verification**: Test all URLs and login credentials
- [ ] **SSL Configuration**: Verify HTTPS access works (Docker setup)
- [ ] **Database Connectivity**: Confirm database access and schema

### Phase 2: Functional Testing (Days 2-3)
- [ ] **User Registration**: Test new user signup process
- [ ] **Business Listing**: Create complete business listing
- [ ] **Review System**: Submit and approve reviews
- [ ] **Search Functionality**: Test all search and filter options
- [ ] **Moderation Workflow**: Test content approval process

### Phase 3: Business Process Testing (Days 4-5)
- [ ] **Payment Processing**: Test subscription purchases (test mode)
- [ ] **Sponsored Listings**: Verify sponsored placement works
- [ ] **Advertisement System**: Test ad placement and tracking
- [ ] **Email Notifications**: Verify all email triggers work
- [ ] **Admin Functions**: Test administrative management

### Phase 4: Performance & Security (Days 6-7)
- [ ] **Load Testing**: Test with multiple concurrent users
- [ ] **Security Scanning**: Run security vulnerability scans
- [ ] **Mobile Testing**: Test on actual mobile devices
- [ ] **Browser Compatibility**: Test on all major browsers
- [ ] **Accessibility**: Validate WCAG compliance

### 🚀 **Phase 5: Production Readiness (NEW)**
- [ ] **Container Orchestration**: Verify service dependencies and health checks
- [ ] **Backup Testing**: Test automated backup and restore functionality
- [ ] **Monitoring**: Validate Prometheus metrics and Grafana dashboards
- [ ] **SSL Certificate**: Test Let's Encrypt certificate renewal
- [ ] **Performance Optimization**: Verify Redis caching and OPCache effectiveness

---

## 🔧 TROUBLESHOOTING

### Docker Environment Issues
```bash
# Check all container status
./deploy.sh status

# View specific service logs
./deploy.sh logs nginx
./deploy.sh logs php
./deploy.sh logs db
./deploy.sh logs redis

# Restart services
./deploy.sh restart

# Access container shell
./deploy.sh shell php
./deploy.sh shell nginx

# Rebuild containers
docker-compose build --no-cache
./deploy.sh deploy
```

### Common Docker Issues

#### Issue: "Port already in use"
```bash
# Solution: Check what's using the port
sudo lsof -i :80
sudo lsof -i :3306
# Stop conflicting services or change ports in .env
```

#### Issue: "Permission denied" for data directories
```bash
# Solution: Fix directory permissions
sudo chown -R www-data:www-data data/
sudo chmod -R 755 logs/
sudo chmod -R 755 backups/
```

#### Issue: "Database connection failed"
```bash
# Solution: Check MySQL container
./deploy.sh logs db
docker-compose exec db mysql -u root -p
# Verify credentials in .env file
```

#### Issue: "Website not accessible"
```bash
# Solution: Check Nginx container and PHP-FPM
./deploy.sh logs nginx
./deploy.sh logs php
# Verify container health
docker-compose ps
```

### Lightweight UAT Issues

#### Issue: "Permission Denied" during setup
```bash
# Solution: Ensure user has sudo privileges
sudo usermod -aG sudo $USER
# Then logout and login again
```

#### Issue: "PHP server won't start"
```bash
# Solution: Check port availability
lsof -i:8080
# Kill existing processes
pkill -f "php -S"
# Try alternative port
UAT_PORT=8081 ./setup-lightweight-uat.sh
```

#### Issue: "Plugin not working"
```bash
# Solution: Check plugin status and permissions
cd /home/ankur/workspace/biz-dir/uat-environment
chmod 644 *.php
chmod 755 .
```

### Getting Help

1. **Check Log Files**:
   ```bash
   sudo tail -f /var/log/apache2/bizdir-uat-error.log
   sudo tail -f /var/log/bizdir-uat/debug.log
   ```

2. **WordPress Debug Mode**:
   ```bash
   # Edit wp-config.php to enable debug
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

3. **Test Results Review**:
   ```bash
   # View detailed test results
   cat /home/ankur/biz-dir-test-results/uat/test_execution.csv
   firefox /home/ankur/biz-dir-test-results/uat/uat_test_report.html
   ```

---

## 📊 SUCCESS CRITERIA

### Must Pass (Critical)
- [ ] All basic connectivity tests pass
- [ ] WordPress core functionality works
- [ ] Database schema complete and accessible
- [ ] User authentication functioning
- [ ] No critical security vulnerabilities

### Should Pass (Important)
- [ ] Page load times under 3 seconds
- [ ] All payment gateways in test mode
- [ ] Email notifications working
- [ ] Mobile responsive design
- [ ] Cross-browser compatibility

### Nice to Have (Enhancement)
- [ ] SSL certificate properly configured
- [ ] Advanced performance optimization
- [ ] Comprehensive error handling
- [ ] Detailed analytics tracking
- [ ] API endpoints functional

---

## 🎯 TEST SCENARIOS BY USER TYPE

### Business Owner Testing
1. **Registration Process**:
   - Create new account
   - Verify email (check email system)
   - Complete profile setup

2. **Business Management**:
   - Add new business listing
   - Upload business images
   - Update business information
   - View business analytics

3. **Subscription Management**:
   - Browse subscription plans
   - Purchase sponsored listing
   - Verify payment processing
   - Check sponsored benefits

### Customer Testing
1. **Discovery Process**:
   - Search for businesses by location
   - Filter by category and rating
   - Browse business listings
   - View business details

2. **Review Process**:
   - Submit business review
   - Rate business (fractional ratings)
   - View review moderation status
   - Receive approval notifications

### Moderator Testing
1. **Content Moderation**:
   - Access moderation queue
   - Review pending business listings
   - Approve/reject content
   - Send feedback to contributors

2. **Quality Control**:
   - Monitor review quality
   - Handle spam reports
   - Escalate issues to admins
   - Track moderation metrics

### Administrator Testing
1. **Platform Management**:
   - Configure system settings
   - Manage user accounts
   - View platform analytics
   - Configure payment gateways

2. **Content Management**:
   - Bulk operations on listings
   - Manage categories and towns
   - Configure advertisement slots
   - Generate platform reports

---

## 📈 PERFORMANCE BENCHMARKS

### Target Metrics
| Metric | Target | Measurement Method |
|--------|--------|--------------------|
| Page Load Time | < 3 seconds | Chrome DevTools |
| Server Response | < 500ms | curl timing |
| Database Queries | < 100ms avg | MySQL slow query log |
| Concurrent Users | 100+ users | Load testing tools |
| Mobile Score | > 90 | Google PageSpeed |

### Load Testing Commands
```bash
# Simple load test with curl
for i in {1..50}; do
  curl -w "Response time: %{time_total}s\n" -o /dev/null -s http://uat.biz-dir.local &
done
wait

# Monitor server resources during test
top -p $(pgrep -f apache2)
```

---

## 🔒 SECURITY TESTING

### Security Checklist
- [ ] **Input Validation**: Test form inputs with malicious data
- [ ] **Authentication**: Test login/logout functionality
- [ ] **Authorization**: Verify role-based access control
- [ ] **Session Management**: Test session timeout and security
- [ ] **SQL Injection**: Test database query protection
- [ ] **XSS Protection**: Test script injection prevention
- [ ] **CSRF Protection**: Verify nonce validation
- [ ] **File Upload Security**: Test image upload restrictions

### Security Test Commands
```bash
# Basic security header check
curl -I http://uat.biz-dir.local

# Test for common vulnerabilities
nikto -h http://uat.biz-dir.local

# SSL certificate check
openssl s_client -connect uat.biz-dir.local:443 -servername uat.biz-dir.local
```

---

## 📱 MOBILE TESTING

### Device Testing Matrix
| Device Type | Screen Size | Browser | Priority |
|-------------|-------------|---------|----------|
| iPhone 12/13 | 390x844 | Safari | High |
| Samsung Galaxy | 360x640 | Chrome | High |
| iPad | 768x1024 | Safari | Medium |
| Android Tablet | 600x960 | Chrome | Medium |

### Mobile Test Scenarios
1. **Navigation Testing**:
   - Test hamburger menu functionality
   - Verify touch targets are adequate size
   - Check scrolling and swiping gestures

2. **Form Testing**:
   - Test virtual keyboard behavior
   - Verify form field focus and input
   - Check form validation messages

3. **Performance Testing**:
   - Test on 3G network simulation
   - Verify image loading and optimization
   - Check battery usage impact

---

## 📋 FINAL SIGN-OFF CHECKLIST

### Technical Sign-off
- [ ] **Environment Setup**: UAT environment fully configured
- [ ] **Automated Tests**: All validation tests passing
- [ ] **Manual Tests**: All manual test scenarios completed
- [ ] **Performance**: All performance benchmarks met
- [ ] **Security**: Security testing completed with no critical issues

### Business Sign-off
- [ ] **User Workflows**: All user journeys tested and approved
- [ ] **Business Processes**: All business requirements validated
- [ ] **Content Management**: Moderation workflow tested and approved
- [ ] **Monetization**: Payment processing tested in sandbox mode
- [ ] **Reporting**: Analytics and reporting functionality verified

### Quality Assurance Sign-off
- [ ] **Bug Tracking**: All bugs logged and prioritized
- [ ] **Test Coverage**: 100% of requirements tested
- [ ] **Documentation**: All test results documented
- [ ] **Risk Assessment**: All risks identified and mitigated
- [ ] **Go-Live Readiness**: Platform ready for production deployment

---

**🎉 When all checklists are complete, the platform is ready for production deployment! 🎉**

---

**Document Maintained By**: UAT Team  
**Last Updated**: August 23, 2025  
**Review Schedule**: Daily during UAT execution
