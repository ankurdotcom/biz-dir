# BizDir Wiki Application - Quick Start Guide

## 🎉 **Your Wiki is Ready!**

### 📍 **Access URLs**
- **🌐 Wiki Application**: http://localhost:3000
- **🔧 Database Admin**: http://localhost:8080

---

## 🚀 **Initial Setup (First Time)**

### Step 1: Open Wiki Application
Visit **http://localhost:3000** in your browser

### Step 2: Complete Setup Wizard
You'll see the Wiki.js setup wizard. Configure:

1. **Site Info**:
   - Site Title: `BizDir Knowledge Base`
   - Site Description: `Business Directory Documentation and Wiki`

2. **Administrator Account**:
   - Email: `admin@bizdir.local`
   - Password: `admin123` (change this!)
   - Full Name: `BizDir Admin`

3. **Database** (already configured):
   - Type: MySQL
   - Host: `wiki_db`
   - Port: `3306`
   - Username: `wiki_user`
   - Password: `wiki_secure_2025!`
   - Database: `wiki_db`

### Step 3: Complete Installation
Click "Install" and wait for the setup to complete.

---

## 👤 **Demo User Account**

Once setup is complete, you can create additional users or use the admin account:

**Admin Account**:
- Username: `admin@bizdir.local`
- Password: `admin123` (or what you set during setup)

---

## 📚 **Getting Started with Your Wiki**

### Creating Your First Page
1. Login to your wiki
2. Click "Create Page" or the `+` button
3. Choose a template (Article, Blog Post, etc.)
4. Start writing!

### Sample Pages to Create
- **BizDir Project Overview**
- **API Documentation**
- **Deployment Guide**
- **Troubleshooting**
- **Network Access Setup Guide**

---

## 🛠 **Management Commands**

Use the `wiki-manager.sh` script for easy management:

```bash
# Start the wiki
./wiki-manager.sh start

# Stop the wiki
./wiki-manager.sh stop

# Restart the wiki
./wiki-manager.sh restart

# Check status
./wiki-manager.sh status

# View logs
./wiki-manager.sh logs

# Create backup
./wiki-manager.sh backup

# Reset everything (DELETE ALL DATA)
./wiki-manager.sh reset
```

---

## 🔧 **Database Administration**

Access **http://localhost:8080** for database management:

**Connection Details**:
- Server: `wiki_db`
- Username: `wiki_user`
- Password: `wiki_secure_2025!`
- Database: `wiki_db`

---

## 🌐 **Network Access**

### Local Access
- http://localhost:3000

### Network Access (from other devices)
Replace `localhost` with your machine's IP address:
- http://YOUR_IP_ADDRESS:3000

To find your IP address:
```bash
ip addr show | grep 'inet ' | grep -v '127.0.0.1'
```

---

## 🔒 **Security Notes**

### Default Credentials
- Change the default admin password after setup
- Consider using stronger passwords in production

### Firewall
If you need external access, ensure ports are open:
```bash
# Allow wiki access
sudo ufw allow 3000

# Allow database admin (optional)
sudo ufw allow 8080
```

---

## 📊 **Features Available**

### Wiki.js Features
- ✅ Rich text editor
- ✅ Markdown support
- ✅ File uploads
- ✅ Search functionality
- ✅ User management
- ✅ Page templates
- ✅ Version history
- ✅ Comments system
- ✅ Tag organization

### Database Management
- ✅ Full MySQL admin via Adminer
- ✅ Query execution
- ✅ Data export/import
- ✅ Table management

---

## 🆘 **Troubleshooting**

### Wiki Not Loading
```bash
# Check container status
./wiki-manager.sh status

# View logs
./wiki-manager.sh logs

# Restart services
./wiki-manager.sh restart
```

### Database Connection Issues
1. Verify database container is running
2. Check database credentials
3. Use Adminer to test connection

### Port Conflicts
If ports 3000 or 8080 are busy, edit `docker-compose-wiki.yml`:
```yaml
ports:
  - "3001:3000"  # Change 3000 to 3001
```

---

## 🎯 **Next Steps**

1. **Complete the setup wizard** at http://localhost:3000
2. **Create your admin account**
3. **Start documenting your BizDir project**
4. **Invite team members** (if applicable)
5. **Customize the wiki** appearance and settings

---

## 📞 **Support**

For Wiki.js documentation: https://docs.requarks.io/
For this setup: Check the `wiki-manager.sh` script options

---

**🎉 Happy Wiki Building!** 📖
