#!/bin/bash
#
# BizDir Wiki Document Sync Script
# Syncs project documentation to Wiki.js with proper categorization and access control
#

set -e

# Configuration
WIKI_URL="http://localhost:3000"
WIKI_API_URL="$WIKI_URL/graphql"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WIKI_DOCS_DIR="./wiki"
SYNC_LOG="./logs/wiki-sync.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$SYNC_LOG"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
    echo "[ERROR] $1" >> "$SYNC_LOG"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
    echo "[WARNING] $1" >> "$SYNC_LOG"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

# Document categories and their access levels
declare -A DOC_CATEGORIES=(
    # Public Documents (all users can read)
    ["README.md"]="public:project-overview"
    ["WIKI_SETUP_GUIDE.md"]="public:setup-guides"
    ["PROJECT_SETUP_GUIDE.md"]="public:setup-guides"
    ["CONTRIBUTORS.md"]="public:project-info"
    
    # Development Documents (developers and admins)
    ["CONFIGURATION_GUIDE.md"]="developer:development"
    ["SETUP_EXTERNAL_CONFIG.md"]="developer:development"
    ["NETWORK_ACCESS_SETUP.md"]="developer:development"
    ["SEED_DATA_DOCUMENTATION.md"]="developer:development"
    
    # Operations Documents (ops team and admins)
    ["DOCKER_INFRASTRUCTURE_EXECUTIVE_SUMMARY.md"]="operations:infrastructure"
    ["DOCKER_OPTIMIZATION_REPORT.md"]="operations:infrastructure"
    ["DOCKER_UAT_EXECUTION_GUIDE.md"]="operations:testing"
    
    # Testing Documents (qa team and admins)
    ["UAT_QUICK_START_GUIDE.md"]="qa:testing"
    ["UAT_PHASE1_COMPLETION_REPORT.md"]="qa:testing"
    ["PRE_PROD_UAT_PLAN.md"]="qa:testing"
    ["TEST_RESULTS_MANAGEMENT.md"]="qa:testing"
    
    # Management Documents (admin only)
    ["EXECUTIVE_SUMMARY.md"]="admin:management"
    ["COMMIT_READINESS_SUMMARY.md"]="admin:management"
    ["GITIGNORE_UPDATE_SUMMARY.md"]="admin:management"
)

# Access level definitions
declare -A ACCESS_LEVELS=(
    ["public"]="read:everyone"
    ["developer"]="read:developers,admins write:developers,admins"
    ["qa"]="read:qa,admins write:qa,admins"
    ["operations"]="read:operations,admins write:operations,admins"
    ["admin"]="read:admins write:admins"
)

# Create wiki directory structure
create_wiki_structure() {
    log "Creating wiki directory structure..."
    
    mkdir -p "$WIKI_DOCS_DIR"/{public,developer,qa,operations,admin}
    mkdir -p "$WIKI_DOCS_DIR"/public/{project-overview,setup-guides,project-info}
    mkdir -p "$WIKI_DOCS_DIR"/developer/{development,configuration}
    mkdir -p "$WIKI_DOCS_DIR"/qa/{testing,reports}
    mkdir -p "$WIKI_DOCS_DIR"/operations/{infrastructure,deployment}
    mkdir -p "$WIKI_DOCS_DIR"/admin/{management,reports}
    
    # Create logs directory
    mkdir -p "$(dirname "$SYNC_LOG")"
    
    log "✅ Wiki directory structure created"
}

# Convert markdown to wiki format
convert_markdown() {
    local source_file="$1"
    local target_file="$2"
    local category="$3"
    local access_level="$4"
    
    # Add wiki metadata header
    # Create title from filename
    local title=$(basename "$source_file" .md | sed 's/_/ /g' | sed 's/\b\w/\U&/g')
    local current_date=$(date -Iseconds)
    
    cat > "$target_file" << EOF
---
title: $title
description: Auto-synced from project documentation
published: true
date: $current_date
tags: [$category, auto-sync, $(echo "$access_level" | cut -d: -f1)]
editor: markdown
dateCreated: $current_date
---

EOF
    
    # Add the original content
    cat "$source_file" >> "$target_file"
    
    log "Converted: $source_file -> $target_file"
}

# Sync individual document
sync_document() {
    local doc_file="$1"
    local category_info="$2"
    
    local access_level=$(echo "$category_info" | cut -d: -f1)
    local category=$(echo "$category_info" | cut -d: -f2)
    
    local target_dir="$WIKI_DOCS_DIR/$access_level/$category"
    local target_file="$target_dir/$(basename "$doc_file")"
    
    # Create target directory if it doesn't exist
    mkdir -p "$target_dir"
    
    # Convert and copy
    if [ -f "$doc_file" ]; then
        convert_markdown "$doc_file" "$target_file" "$category" "$access_level"
        info "📄 Synced: $doc_file -> $access_level/$category/"
        return 0
    else
        warning "File not found: $doc_file"
        return 1
    fi
}

# Create index pages for each category
create_index_pages() {
    log "Creating category index pages..."
    
    local current_date=$(date -Iseconds)
    
    # Public index
    cat > "$WIKI_DOCS_DIR/public/README.md" << EOF
---
title: Public Documentation
description: Documentation accessible to all users
published: true
date: $current_date
tags: [index, public]
editor: markdown
---

# Public Documentation

Welcome to the BizDir public documentation. This section contains information accessible to all users.

## 📋 Project Overview
- Project documentation and general information
- Getting started guides
- Contributor information

## 🛠 Setup Guides  
- Installation and setup instructions
- Configuration guides
- Quick start tutorials

## 👥 Project Information
- Contributors and team information
- Project structure and organization
- Community guidelines

---
*This documentation is automatically synced from the project repository.*
EOF

    # Developer index
    cat > "$WIKI_DOCS_DIR/developer/README.md" << EOF
---
title: Developer Documentation
description: Technical documentation for developers
published: true
date: $current_date
tags: [index, developer]
editor: markdown
---

# Developer Documentation

Technical documentation and guides for developers working on BizDir.

## 🔧 Development
- Configuration and setup guides
- Development environment setup
- API documentation

## ⚙️ Configuration
- System configuration
- Environment variables
- External service setup

---
*Access Level: Developers and Administrators*
EOF

    # QA index
    cat > "$WIKI_DOCS_DIR/qa/README.md" << EOF
---
title: QA Documentation
description: Quality assurance and testing documentation
published: true
date: $current_date
tags: [index, qa]
editor: markdown
---

# QA Documentation

Quality assurance documentation and testing procedures.

## 🧪 Testing
- Test procedures and guidelines
- UAT documentation
- Test result management

## 📊 Reports
- Testing reports and results
- Quality metrics
- Test completion reports

---
*Access Level: QA Team and Administrators*
EOF

    # Operations index
    cat > "$WIKI_DOCS_DIR/operations/README.md" << EOF
---
title: Operations Documentation
description: Infrastructure and operations documentation
published: true
date: $current_date
tags: [index, operations]
editor: markdown
---

# Operations Documentation

Infrastructure, deployment, and operations documentation.

## 🏗 Infrastructure
- Docker infrastructure setup
- System architecture
- Performance optimization

## 🚀 Deployment
- Deployment procedures
- Environment management
- Monitoring and maintenance

---
*Access Level: Operations Team and Administrators*
EOF

    # Admin index
    cat > "$WIKI_DOCS_DIR/admin/README.md" << EOF
---
title: Administrator Documentation
description: Management and administrative documentation
published: true
date: $current_date
tags: [index, admin]
editor: markdown
---

# Administrator Documentation

Management and administrative documentation for project leaders.

## 📈 Management
- Executive summaries
- Project status reports
- Strategic documentation

## 📋 Reports
- Completion reports
- System reports
- Administrative procedures

---
*Access Level: Administrators Only*
EOF

    log "✅ Category index pages created"
}

# Main sync function
sync_all_documents() {
    log "Starting document sync process..."
    
    create_wiki_structure
    
    local synced_count=0
    local failed_count=0
    
    # Sync categorized documents
    for doc_file in "${!DOC_CATEGORIES[@]}"; do
        if sync_document "$doc_file" "${DOC_CATEGORIES[$doc_file]}"; then
            ((synced_count++))
        else
            ((failed_count++))
        fi
    done
    
    # Sync additional markdown files
    info "Scanning for additional markdown files..."
    while IFS= read -r -d '' file; do
        local rel_path="${file#./}"
        if [[ ! "${DOC_CATEGORIES[$rel_path]}" ]]; then
            # Uncategorized files go to developer section
            sync_document "$rel_path" "developer:uncategorized"
            ((synced_count++))
        fi
    done < <(find . -name "*.md" -not -path "./wiki/*" -not -path "./node_modules/*" -not -path "./.git/*" -print0)
    
    create_index_pages
    
    log "✅ Sync complete: $synced_count documents synced, $failed_count failed"
    
    # Generate sync report
    generate_sync_report "$synced_count" "$failed_count"
}

# Generate sync report
generate_sync_report() {
    local synced=$1
    local failed=$2
    
    cat > "$WIKI_DOCS_DIR/sync-report.md" << EOF
---
title: Wiki Sync Report
description: Latest synchronization report
published: true
date: $(date -Iseconds)
tags: [report, sync, auto-generated]
editor: markdown
---

# Wiki Sync Report

**Sync Date**: $(date)
**Documents Synced**: $synced
**Failed**: $failed
**Status**: $([ $failed -eq 0 ] && echo "✅ Success" || echo "⚠️ Partial Success")

## Document Categories

### 📖 Public Documentation
- Project overviews and setup guides
- Accessible to all users

### 👨‍💻 Developer Documentation  
- Technical guides and configuration
- Access: Developers + Admins

### 🧪 QA Documentation
- Testing procedures and reports
- Access: QA Team + Admins

### 🏗 Operations Documentation
- Infrastructure and deployment
- Access: Operations Team + Admins

### 👑 Administrator Documentation
- Management and executive reports
- Access: Admins Only

## Recent Changes
$(find "$WIKI_DOCS_DIR" -name "*.md" -newer ".git/HEAD" 2>/dev/null | head -10 | sed 's|.*/||' | sed 's|^|- |' || echo "- No recent changes detected")

---
*Report generated automatically by wiki-sync script*
EOF

    info "📊 Sync report generated: $WIKI_DOCS_DIR/sync-report.md"
}

# Check wiki status
check_wiki_status() {
    if curl -s -o /dev/null -w "%{http_code}" "$WIKI_URL" | grep -q "200\|302"; then
        log "✅ Wiki is accessible at $WIKI_URL"
        return 0
    else
        error "❌ Wiki is not accessible at $WIKI_URL"
        return 1
    fi
}

# Create access control documentation
create_access_control_doc() {
    local current_date=$(date -Iseconds)
    cat > "$WIKI_DOCS_DIR/ACCESS_CONTROL.md" << EOF
---
title: Wiki Access Control Guide
description: User access levels and permissions
published: true
date: $current_date
tags: [access-control, permissions, admin]
editor: markdown
---

# Wiki Access Control Guide

## 👥 User Roles and Access Levels

### 🌐 Public Users
**Access**: Read-only access to public documentation
- Project overview and setup guides
- General project information
- Getting started tutorials

### 👨‍💻 Developers
**Access**: Read/Write access to development documentation
- All public documentation
- Technical configuration guides
- Development environment setup
- API documentation

### 🧪 QA Team
**Access**: Read/Write access to testing documentation
- All public documentation
- Testing procedures and guidelines
- UAT documentation
- Test reports and results

### 🏗 Operations Team
**Access**: Read/Write access to infrastructure documentation
- All public documentation
- Infrastructure setup and management
- Deployment procedures
- System monitoring and maintenance

### 👑 Administrators
**Access**: Full access to all documentation
- All documentation categories
- User management
- System administration
- Executive reports and summaries

## 🔐 Setting Up Access Control

### Creating User Groups
1. Go to **Administration** → **Groups**
2. Create groups: `developers`, `qa`, `operations`, `admins`
3. Assign appropriate permissions to each group

### Page Permissions
Use page rules to restrict access:
- **Public pages**: No restrictions
- **Developer pages**: Require `developers` or `admins` group
- **QA pages**: Require `qa` or `admins` group  
- **Operations pages**: Require `operations` or `admins` group
- **Admin pages**: Require `admins` group only

### User Assignment
1. Create user accounts for team members
2. Assign users to appropriate groups
3. Test access to ensure proper restrictions

---
*Configure these access controls in your Wiki.js administration panel*
EOF

    log "✅ Access control documentation created"
}

# Watch mode for continuous sync
watch_mode() {
    log "Starting watch mode for continuous sync..."
    
    if ! command -v inotifywait &> /dev/null; then
        error "inotifywait not found. Install inotify-tools for watch mode."
        exit 1
    fi
    
    while true; do
        inotifywait -r -e modify,create,delete --include="\.md$" "." 2>/dev/null
        log "Change detected, syncing..."
        sync_all_documents
        sleep 5
    done
}

# Show help
show_help() {
    cat << EOF
BizDir Wiki Document Sync Script

Usage: $0 {sync|watch|status|setup|help}

Commands:
  sync    - Sync all documents to wiki (one-time)
  watch   - Start continuous sync mode (monitors file changes)
  status  - Check wiki status and show sync statistics  
  setup   - Create initial wiki structure and access control docs
  help    - Show this help message

Document Categories:
  📖 Public      - Accessible to all users
  👨‍💻 Developer   - Technical documentation (developers + admins)
  🧪 QA          - Testing documentation (qa + admins)
  🏗 Operations  - Infrastructure docs (operations + admins)  
  👑 Admin       - Management docs (admins only)

Files:
  Wiki Directory: $WIKI_DOCS_DIR
  Sync Log: $SYNC_LOG
  Wiki URL: $WIKI_URL

Examples:
  $0 sync                    # Sync all documents once
  $0 watch                   # Start monitoring for changes
  $0 setup                   # Initial setup
EOF
}

# Main script logic
case "${1:-help}" in
    sync)
        check_wiki_status && sync_all_documents
        ;;
    watch)
        check_wiki_status && watch_mode
        ;;
    status)
        check_wiki_status
        info "📊 Wiki directory: $WIKI_DOCS_DIR"
        info "📝 Sync log: $SYNC_LOG"
        [ -f "$SYNC_LOG" ] && echo "Recent log entries:" && tail -5 "$SYNC_LOG"
        ;;
    setup)
        check_wiki_status && create_wiki_structure && create_access_control_doc
        info "✅ Setup complete. Run '$0 sync' to sync documents."
        ;;
    help|*)
        show_help
        ;;
esac
