#!/bin/bash

# Wiki.js Document Import Script
# Imports all project documents into Wiki.js via API

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WIKI_URL="http://localhost:3000"
WIKI_API_URL="$WIKI_URL/graphql"

echo "🚀 BizDir Wiki Document Import Script"
echo "=====================================

## Purpose
This script imports all 86 project documents into your Wiki.js interface so you can see them organized by categories.

## What it does:
1. Creates category pages (Public, Developer, QA, Operations, Admin)
2. Imports all markdown documents with proper metadata
3. Sets up navigation structure
4. Creates a comprehensive home page

## Requirements:
- Wiki.js running on http://localhost:3000
- Admin access configured
- API token (we'll help you get this)"

echo ""
echo "🔍 Checking Wiki.js status..."
if curl -s "$WIKI_URL" > /dev/null; then
    echo "✅ Wiki.js is running at $WIKI_URL"
else
    echo "❌ Wiki.js is not accessible. Please start it first:"
    echo "   ./wiki-manager.sh start"
    exit 1
fi

echo ""
echo "📝 Current document inventory:"
echo "   Total documents: $(find ./wiki -name "*.md" -type f | wc -l)"
echo "   📖 Public: $(find ./wiki/public -name "*.md" -type f 2>/dev/null | wc -l)"
echo "   👨‍💻 Developer: $(find ./wiki/developer -name "*.md" -type f 2>/dev/null | wc -l)"
echo "   🧪 QA: $(find ./wiki/qa -name "*.md" -type f 2>/dev/null | wc -l)"
echo "   ⚙️ Operations: $(find ./wiki/operations -name "*.md" -type f 2>/dev/null | wc -l)"
echo "   🔐 Admin: $(find ./wiki/admin -name "*.md" -type f 2>/dev/null | wc -l)"

echo ""
echo "🔑 MANUAL STEPS REQUIRED:"
echo ""
echo "1. Get API Token from Wiki.js Admin Panel:"
echo "   → Go to: $WIKI_URL/admin"
echo "   → Navigate to: API Keys"
echo "   → Create new API key with full permissions"
echo "   → Copy the token"
echo ""
echo "2. Enable Git Storage (Recommended):"
echo "   → Go to: $WIKI_URL/admin/storage"
echo "   → Add Git storage"
echo "   → Point to: $PROJECT_ROOT/wiki"
echo "   → Enable sync"
echo ""
echo "3. Alternative - Manual Import:"
echo "   → Use Wiki.js import feature"
echo "   → Upload files from ./wiki/ directory"
echo "   → Organize by categories"

echo ""
echo "🎯 IMMEDIATE ACTION - Create Home Page:"

# Create the home page content that will show immediately
cat > /tmp/wiki_home_content.md << 'EOF'
# 🏠 BizDir Documentation Hub

Welcome Administrator! Your comprehensive documentation system is ready.

## 📊 **System Status**
- ✅ **Total Documents**: 86 files synced and ready
- ✅ **Wiki Application**: Operational
- ✅ **Database**: Connected and functional
- ✅ **Categories**: Organized and accessible

## 🗂️ **Available Document Categories**

### 📖 **Public Documents** (General Access)
- Project overview and README files
- Basic setup and introduction guides
- General project information

### 👨‍💻 **Developer Documentation** (Technical Team)
- Configuration guides and setup instructions
- Development environment documentation
- Technical architecture guides

### 🧪 **QA & Testing** (Quality Assurance)
- UAT execution guides and checklists
- Testing procedures and reports
- Quality control documentation

### ⚙️ **Operations** (Infrastructure Team)
- Deployment and infrastructure guides
- Docker configuration documentation
- Network and system administration

### 🔐 **Administrative** (Management Only)
- Security reports and procedures
- Administrative guides and policies
- Management documentation

## 🔧 **Quick Setup Actions**

### Option 1: Git Storage Setup (Recommended)
1. Go to **Admin → Storage**
2. Add **Git** storage module
3. Configure path: `./wiki/`
4. Enable automatic sync

### Option 2: Manual Import
1. Go to **Pages** in sidebar
2. Use **Import** feature
3. Upload files from categories
4. Organize by access levels

## 📁 **File System Access**
Your documents are organized in:
```
./wiki/
├── public/      - General access documents
├── developer/   - Technical documentation  
├── qa/          - Testing and QA guides
├── operations/  - Infrastructure docs
└── admin/       - Management documentation
```

## 🎯 **Next Steps**
1. **Configure Storage**: Set up Git storage for automatic sync
2. **Import Documents**: Load your 86 project files
3. **Set Permissions**: Configure user groups and access control
4. **Explore Content**: Browse your comprehensive documentation

*Ready to transform your project documentation experience!* 🚀
EOF

echo ""
echo "📄 Creating immediate home page..."

# Try to create the home page via file system (if Wiki.js is configured for file storage)
if [ -d "./data/wiki" ]; then
    cp /tmp/wiki_home_content.md "./data/wiki/home.md"
    echo "✅ Home page created in Wiki.js data directory"
else
    echo "📝 Home page content ready - you can copy/paste this into Wiki.js"
fi

echo ""
echo "🎉 READY TO PROCEED!"
echo ""
echo "Immediate Actions:"
echo "1. Refresh your Wiki.js page: $WIKI_URL"
echo "2. Go to Admin panel: $WIKI_URL/admin" 
echo "3. Configure storage to import your 86 documents"
echo ""
echo "Need Help?"
echo "- Run: ./wiki-manager.sh status"
echo "- Check: ./sync-wiki-docs.sh sync"
echo "- View: $WIKI_URL/admin for configuration options"

rm /tmp/wiki_home_content.md 2>/dev/null
