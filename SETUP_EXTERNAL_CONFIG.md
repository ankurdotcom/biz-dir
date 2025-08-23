# 🔐 CRITICAL: External Configuration Setup Required

## ⚠️ BEFORE DEPLOYMENT: Set Up External Configuration Management

This project uses **external configuration management** for security. You **MUST** set up the external configuration directory before deploying to any environment.

### 🚨 Quick Setup (Required)

```bash
# 1. Create external configuration directory
mkdir -p /home/ankur/biz-dir-configs/{development,staging,production}

# 2. Create environment-specific configurations
# Development
cat > /home/ankur/biz-dir-configs/development/wp-config.php << 'EOF'
<?php
define('DB_NAME', 'biz_directory_dev');
define('DB_USER', 'dev_user');
define('DB_PASSWORD', 'dev_password_here');
define('DB_HOST', 'localhost');
define('WP_DEBUG', true);
$table_prefix = 'wp_dev_';
// Add your development-specific settings
EOF

# 3. Create production configuration template
cat > /home/ankur/biz-dir-configs/production/wp-config.php << 'EOF'
<?php
define('DB_NAME', 'biz_directory_prod');
define('DB_USER', 'prod_user');
define('DB_PASSWORD', 'REPLACE_WITH_SECURE_PASSWORD');
define('DB_HOST', 'localhost');
define('WP_DEBUG', false);
$table_prefix = 'wp_prod_';
// Add production security keys from https://api.wordpress.org/secret-key/1.1/salt/
EOF

# 4. Set secure permissions
chmod 600 /home/ankur/biz-dir-configs/*/wp-config.php
```

### 📚 Complete Documentation

For detailed setup instructions, see:
- **[CONFIGURATION_GUIDE.md](CONFIGURATION_GUIDE.md)** - Complete configuration management
- **[PROJECT_SETUP_GUIDE.md](PROJECT_SETUP_GUIDE.md)** - New machine setup instructions

### 🔧 Validation

Run the maintenance script to validate your setup:
```bash
./maintain-documentation.sh validate
```

### 🔒 Security Checklist

- [ ] External configuration directory created
- [ ] Environment-specific wp-config.php files created  
- [ ] Secure file permissions set (600)
- [ ] No sensitive data in git repository
- [ ] .gitignore properly configured
- [ ] Maintenance script runs without errors

---

**Remember**: Never commit real credentials to the repository. Always use the external configuration system!
