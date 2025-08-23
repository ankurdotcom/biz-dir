# Business Directory WordPress Plugin

A comprehensive business directory plugin for WordPress with advanced search, rating systems, and monetization features.

## 🚀 Quick Start

### 🔥 **NEW: Production Docker Setup (Recommended)**
```bash
# Clone the repository
git clone https://github.com/ankurdotcom/biz-dir.git
cd biz-dir

# Copy and configure environment
cp .env.example .env
nano .env  # Edit your configuration

# Deploy production-grade environment
./deploy.sh deploy

# Access your application
open http://localhost
```

### 🛠️ **Alternative: Manual Installation**
```bash
# Install dependencies
cd mvp && composer install

# Set up external configurations (REQUIRED)
# See SETUP_EXTERNAL_CONFIG.md for instructions

# Run tests to validate setup
./run-tests.sh
```

### ⚠️ Important: Configuration Setup Required
**Before deploying manually**, you must set up external configuration management for security. See **[SETUP_EXTERNAL_CONFIG.md](SETUP_EXTERNAL_CONFIG.md)** for quick setup instructions.

## 📚 Documentation

### � **Docker Production Setup** 
- **[docker/README.md](docker/README.md)** - 🔥 **NEW**: Complete Docker production guide
- **[DOCKER_UAT_EXECUTION_GUIDE.md](DOCKER_UAT_EXECUTION_GUIDE.md)** - Docker-based UAT testing
- **[.env.example](.env.example)** - Environment configuration template

### �🔧 Setup and Configuration
- **[SETUP_EXTERNAL_CONFIG.md](SETUP_EXTERNAL_CONFIG.md)** - ⚠️ **REQUIRED**: External configuration setup
- **[PROJECT_SETUP_GUIDE.md](PROJECT_SETUP_GUIDE.md)** - Complete setup instructions for new machines
- **[CONFIGURATION_GUIDE.md](CONFIGURATION_GUIDE.md)** - Comprehensive configuration management guide

### 🧠 Knowledge Base
- **[mvp/tests/KNOWLEDGE_TRACKER.md](mvp/tests/KNOWLEDGE_TRACKER.md)** - Testing methodology and technical learnings
- **[mvp/tests/AI_TROUBLESHOOTING_TRACKER.md](mvp/tests/AI_TROUBLESHOOTING_TRACKER.md)** - AI-assisted debugging patterns
- **[mvp/tests/UAT_READINESS_EXECUTIVE_SUMMARY.md](mvp/tests/UAT_READINESS_EXECUTIVE_SUMMARY.md)** - Quality assurance status

### 🚀 Deployment and Operations
- **[mvp/deploy/DEPLOYMENT_CHECKLIST.md](mvp/deploy/DEPLOYMENT_CHECKLIST.md)** - Production deployment procedures
- **[mvp/UAT_CHECKLIST.md](mvp/UAT_CHECKLIST.md)** - User acceptance testing checklist
- **[mvp/UAT_IMMEDIATE_ACTION_PLAN.md](mvp/UAT_IMMEDIATE_ACTION_PLAN.md)** - Critical issue response plan

## 🛠 Development

### Testing Framework
```bash
# Run full test suite
cd mvp && ./run-tests.sh

# Run regression tests
./run-regression-tests.sh

# Run specific test categories
./vendor/bin/phpunit tests/Business/
./vendor/bin/phpunit tests/User/
```

### Maintenance
```bash
# Validate documentation and configuration
./maintain-documentation.sh

# Check for outdated dependencies
./maintain-documentation.sh validate

# Update documentation timestamps
./maintain-documentation.sh update
```

## 🔒 Security

This project follows security-first principles:
- External configuration management (no credentials in repository)
- OWASP Top 10:2021 compliance
- Comprehensive input validation
- Secure authentication and session management

## 📊 Project Structure

```
biz-dir/
├── mvp/                           # Main plugin code
│   ├── config/                    # Database schemas
│   ├── tests/                     # Comprehensive test suite
│   ├── deploy/                    # Deployment configurations
│   └── vendor/                    # PHP dependencies
├── wiki/                          # Wiki and documentation system
├── prompt/                        # Project specifications
├── CONFIGURATION_GUIDE.md         # Configuration management
├── PROJECT_SETUP_GUIDE.md         # Complete setup guide
├── SETUP_EXTERNAL_CONFIG.md       # Quick configuration setup
└── maintain-documentation.sh      # Documentation maintenance tool
```

## 🤝 Contributing

1. Read the [CONFIGURATION_GUIDE.md](CONFIGURATION_GUIDE.md) for development setup
2. Follow the testing patterns in [KNOWLEDGE_TRACKER.md](mvp/tests/KNOWLEDGE_TRACKER.md)
3. Use the [AI_TROUBLESHOOTING_TRACKER.md](mvp/tests/AI_TROUBLESHOOTING_TRACKER.md) for debugging assistance
4. Run `./maintain-documentation.sh` before committing to validate setup

## 📞 Support

- **Technical Documentation**: See knowledge trackers in `mvp/tests/`
- **Setup Issues**: Follow [PROJECT_SETUP_GUIDE.md](PROJECT_SETUP_GUIDE.md)
- **Configuration Problems**: See [CONFIGURATION_GUIDE.md](CONFIGURATION_GUIDE.md)

---

**Last Updated**: August 23, 2025
**Maintained By**: Development Team
