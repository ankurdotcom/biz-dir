# Docker & PHP Troubleshooting Knowledge Tracker

## 🎯 Purpose
This knowledge tracker documents troubleshooting patterns, common mistakes, and validated solutions for Docker/PHP/WordPress environments. Optimized for AI tools like GitHub Copilot to reference before attempting fixes.

## 📋 Troubleshooting Methodology

### 1. ANALYSIS FIRST - Don't Jump to Fixes
- **STOP**: Read error messages completely
- **INVESTIGATE**: Check logs, configurations, and current state
- **VALIDATE**: Verify assumptions before making changes
- **PLAN**: Consider multiple approaches before acting

### 2. Systematic Validation Steps
1. **Read Error Messages Carefully** - Don't skim
2. **Check Container Status** - Are services actually running?
3. **Verify Logs** - Look for specific error patterns
4. **Test Connectivity** - Database, Redis, networks
5. **Validate Configuration** - Compare with working examples
6. **Check File Permissions** - Often overlooked cause

---

## 🚨 Common Mistakes & Lessons Learned

### Issue #1: 403 Forbidden Errors
**❌ MISTAKE**: Immediately assumed Nginx configuration problem
**✅ ROOT CAUSE**: Missing WordPress index.php file
**🔍 PROPER ANALYSIS**:
```bash
# Check if index files exist first
ls -la /path/to/webroot/index.*
# Check Nginx logs for specific error
docker logs container_name
# Look for "directory index forbidden" vs other 403 types
```
**📝 LESSON**: File system issues often masquerade as web server problems

### Issue #2: PHP Extension Problems
**❌ MISTAKE**: Repeatedly restarting containers without validating changes
**✅ ROOT CAUSE**: PHP_DISMOD environment variable disabling required extensions
**🔍 PROPER ANALYSIS**:
```bash
# Check what extensions are actually loaded
docker exec container php -m | grep extension_name
# Check PHP configuration that might disable extensions
docker exec container php -r "phpinfo();" | grep extension_name
# Verify environment variables are applied
docker exec container env | grep PHP_
```
**📝 LESSON**: Environment variables can override default configurations

### Issue #3: Network Connectivity Issues
**❌ MISTAKE**: Assuming containers can communicate without proper network setup
**✅ ROOT CAUSE**: Containers not on same Docker network
**🔍 PROPER ANALYSIS**:
```bash
# Check container networks
docker network ls
docker inspect container_name | grep NetworkMode
# Test connectivity between containers
docker exec container ping other_container_name
```
**📝 LESSON**: Docker networking requires explicit configuration in compose files

---

## 🛠️ Systematic Troubleshooting Checklist

### Before Making Any Changes:
- [ ] Read complete error message (don't skip details)
- [ ] Check container status: `docker compose ps`
- [ ] Review recent logs: `docker compose logs service_name --tail=20`
- [ ] Verify file existence and permissions
- [ ] Test basic connectivity (ping, port checks)
- [ ] Check environment variables and configurations

### Docker Container Issues:
```bash
# Standard diagnostic commands
docker compose ps                          # Container status
docker compose logs service_name -f       # Live logs
docker compose exec service_name sh       # Shell access
docker inspect container_name             # Detailed container info
docker network ls                         # Network information
```

### PHP Extension Issues:
```bash
# Check loaded extensions
docker exec container php -m
# Test specific extension
docker exec container php -r "echo extension_loaded('extension_name') ? 'YES' : 'NO';"
# Check PHP configuration
docker exec container php --ini
```

### Database Connectivity:
```bash
# Test database connection
docker exec db_container mysql -u user -p -e "SHOW DATABASES;"
# Test from PHP container
docker exec php_container php -r "new PDO('mysql:host=db;dbname=test', 'user', 'pass');"
```

### File System Issues:
```bash
# Check file permissions
docker exec container ls -la /path/to/files
# Check file ownership
docker exec container stat /path/to/file
# Check disk space
docker exec container df -h
```

---

## 🎯 Solution Patterns

### Pattern 1: Missing PHP Extensions
**Symptoms**: "extension not found" errors
**Validation Steps**:
1. Check if extension is installed: `php -m | grep extension`
2. Check if disabled in config: `env | grep PHP_DISMOD`
3. Test loading manually: `php -r "extension_loaded('name')"`

**Solutions (in order of preference)**:
1. Remove from PHP_DISMOD environment variable
2. Add to PHP_ENABLEMOD environment variable
3. Use different base image with extensions pre-installed
4. Install extension manually in Dockerfile

### Pattern 2: Network Connectivity Issues
**Symptoms**: "connection refused", "host not found"
**Validation Steps**:
1. Check container status: `docker compose ps`
2. Verify network configuration in compose file
3. Test ping between containers
4. Check port bindings

**Solutions**:
1. Add containers to same network in compose file
2. Use service names (not localhost) for inter-container communication
3. Verify port mappings match application configuration

### Pattern 4: Container Startup Timing Issues
**Symptoms**: Extensions/services appear missing despite being configured
**Validation Steps**:
1. Wait adequate time for container startup (15+ seconds for complex containers)
2. Check container status: `docker compose ps`
3. Verify services are actually running: `docker compose logs service_name`
4. Test functionality after confirmed startup

**Solutions**:
1. Always wait for containers to fully initialize
2. Use health checks in compose files for critical services
3. Implement startup delays in testing scripts
4. Monitor logs for "ready" messages before testing

### Pattern 6: WordPress User Management via Backend
**Symptoms**: Need to create/update users without web interface access
**Validation Steps**:
1. Check WordPress installation: `file_exists('wp-config.php')`
2. Verify database tables: `SHOW TABLES LIKE 'wp_%'`
3. Check existing users: `SELECT user_login FROM wp_users`

**Solutions (in order of preference)**:
1. Use PHP script with WordPress functions (wp_create_user, wp_set_password)
2. Use WP-CLI if available in container
3. Direct database manipulation (last resort)
4. Create via WordPress admin interface

**Example Implementation**:
```php
require_once('/var/www/html/wp-load.php');
$user_id = wp_create_user($username, $password, $email);
wp_set_password($password, $user_id); // For existing users
```

### Pattern 7: Systematic Backend Operations
**Approach**: Always validate environment before executing operations
**Steps**:
1. Check service availability
2. Verify data integrity
3. Execute operation
4. Validate results
5. Document success pattern

### Pattern 10: Anonymous User Public Business Directory Implementation
**Context**: Implementing public read-only access to business directory without login requirement
**Validation Steps**:
1. Check WordPress theme structure: `ls wp-content/themes/theme-name/`
2. Verify template hierarchy: front-page.php, single.php, category.php
3. Test public access: curl or browser without authentication
4. Validate responsive design: mobile/desktop layouts
5. Check JavaScript functionality: search, filters, interactions

**Implementation Success Pattern**:
1. **Custom Templates**: Created specialized templates for business directory
   - `front-page.php`: Homepage with hero, search, categories, latest businesses
   - `single.php`: Detailed business pages with public contact info
   - `category.php`: Category browsing with filters and sorting
2. **Public Data Access**: All business information accessible without login
   - Contact details (phone, email, address, hours) visible to all
   - Business descriptions, ratings, and features publicly displayed
   - Category navigation and search functionality open access
3. **Registration Encouragement**: Strategic CTAs for user conversion
   - Login modals for restricted features (reviews, favorites)
   - Clear registration benefits highlighted
   - "Login to access more features" prompts
4. **Professional UI/UX**: Modern, mobile-first design implementation
   - Responsive grid layouts for business cards
   - Interactive search with real-time filtering
   - Professional gradient hero sections and clean typography
5. **SEO Optimization**: Search engine friendly structure
   - Clean URLs and proper heading hierarchy
   - Schema.org markup for business listings
   - Meta descriptions and structured data

**Key Learning**: Anonymous access increases discoverability while login prompts drive conversions

---

## 🔄 Learning Updates

### Update Log:
- **2024-12-26**: Initial tracker created
- **Current Session**: Added Docker network, PHP extension, and file permission patterns
- **2025-08-23**: Successfully resolved mysqli extension issue using systematic approach
- **2025-08-23**: Implemented Anonymous User Public Business Directory Access (Prompt 14)

### Key Insights:
1. **Always validate assumptions** before implementing fixes
2. **Check logs first** - they usually contain the real cause
3. **Test connectivity systematically** - network issues are common
4. **Environment variables override defaults** - check them carefully
5. **File system problems often appear as application errors**
6. **Wait for containers to fully start** - premature testing leads to false negatives
7. **Removing restrictive configurations is often better than adding overrides**

### Recent Success Pattern - WordPress Critical Error Resolution:
**Problem**: "There has been a critical error on this website" message
**Wrong Approach**: ❌ Immediately assuming database or server issues
**Right Approach**: ✅ Systematic error analysis:
1. Check container status: `docker compose ps`
2. Read PHP error logs: `docker compose logs php --tail=20`
3. Identify specific error: Missing file `/inc/template-functions.php` line 615
4. Check file structure: `find theme-dir -type f`
5. **Solution**: Create missing required files to resolve include errors

### Pattern 9: WordPress Critical Error Diagnosis
**Symptoms**: "Critical error" message with no specific details
**Validation Steps**:
1. Check PHP error logs first (never skip this step)
2. Look for "Fatal error", "require", "include" messages
3. Verify file paths and directory structure
4. Check for function redeclaration conflicts
5. Test file includes and dependencies

**Solutions (in order of preference)**:
1. Create missing required files based on error messages
2. Fix file paths in require/include statements
3. Resolve function name conflicts
4. Check file permissions and ownership
5. Verify theme/plugin file structure integrity

**Critical Learning**: Always read the actual error message in logs - it tells you exactly what's wrong

### Pattern 8: WordPress Theme/Plugin Management
**Symptoms**: Default theme active instead of custom theme
**Validation Steps**:
1. Check theme availability: `ls wp-content/themes/`
2. Verify theme structure and style.css header
3. Check current active theme: `wp_get_theme()`
4. Test theme activation via admin or PHP script

**Solutions**:
1. Use `switch_theme()` function for theme activation
2. Use `activate_plugin()` for plugin activation
3. Verify file permissions and structure
4. Check for PHP errors in theme/plugin files

---

## 🚀 AI Tool Optimization

### For GitHub Copilot:
```yaml
# Reference this file in comments before troubleshooting:
# @see troubleshooting-tracker.md for common Docker/PHP issues
# Check: container status, logs, connectivity, extensions, permissions
```

### Quick Reference Commands:
```bash
# Docker health check bundle
docker compose ps && docker compose logs --tail=10

# PHP extension validation
docker exec container php -m | grep -E "(mysql|redis|gd)"

# Network connectivity test
docker exec container ping other_service

# File permission check
docker exec container ls -la /var/www/html/
```

---

## 🎯 Future Additions
- WordPress-specific troubleshooting patterns
- SSL/TLS certificate issues
- Performance optimization patterns
- Security configuration patterns
- Database migration issues

---

**Remember**: Analysis before action. Validation before fixes. Documentation after success.
