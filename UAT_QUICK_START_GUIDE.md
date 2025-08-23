# BizDir Pre-Production UAT Quick Start Guide
## User Acceptance Testing Setup and Execution

**Version:** 1.0  
**Date:** August 23, 2025  
**Purpose:** Quick setup and execution guide for UAT testing

---

## 🚀 QUICK START (5 Minutes)

### Step 1: Setup UAT Environment
```bash
# Navigate to project directory
cd /home/ankur/workspace/biz-dir

# Run UAT environment setup (requires sudo)
./setup-uat-environment.sh
```

### Step 2: Run Initial Validation Tests
```bash
# Execute automated UAT validation tests
./run-uat-tests.sh
```

### Step 3: Access UAT Environment
```bash
# URLs (add to /etc/hosts if needed)
http://uat.biz-dir.local          # Main site
https://uat.biz-dir.local         # HTTPS (self-signed)
http://uat.biz-dir.local/wp-admin # WordPress admin
```

### Step 4: Login Credentials
```
WordPress Admin:
- Username: uatadmin
- Password: UATAdmin@2025

Test Users:
- Contributor: testcontributor / TestPass123
- Moderator: testmoderator / TestPass123  
- Business Owner: testbusiness / TestPass123
```

---

## 📋 UAT TESTING CHECKLIST

### Phase 1: Environment Validation (Day 1)
- [ ] **Environment Setup**: Run `./setup-uat-environment.sh`
- [ ] **Basic Tests**: Run `./run-uat-tests.sh`
- [ ] **Access Verification**: Test all URLs and login credentials
- [ ] **SSL Configuration**: Verify HTTPS access works
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

---

## 🔧 TROUBLESHOOTING

### Common Issues

#### Issue: "Permission Denied" during setup
```bash
# Solution: Ensure user has sudo privileges
sudo usermod -aG sudo $USER
# Then logout and login again
```

#### Issue: "Database connection failed"
```bash
# Solution: Check MySQL service
sudo systemctl status mysql
sudo systemctl start mysql
```

#### Issue: "Website not accessible"
```bash
# Solution: Check Apache service and virtual host
sudo systemctl status apache2
sudo a2ensite bizdir-uat
sudo systemctl reload apache2
```

#### Issue: "Plugin not working"
```bash
# Solution: Check plugin status and permissions
cd /var/www/html/bizdir-uat
wp plugin list
wp plugin activate biz-dir-core
sudo chown -R www-data:www-data wp-content/
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
