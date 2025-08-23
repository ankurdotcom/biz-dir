#!/bin/bash
#
# BizDir Wiki Documentation Sync Script
# Syncs project documentation to wiki with proper organization
#

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WIKI_DOCS_DIR="./wiki"
SYNC_LOG="./logs/wiki-sync.log"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"
    mkdir -p "$(dirname "$SYNC_LOG")"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" >> "$SYNC_LOG"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARN] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

# Document organization with access levels and categories
sync_documents() {
    log "Starting comprehensive document sync..."
    
    # Create directory structure
    mkdir -p "$WIKI_DOCS_DIR"/{public,developer,qa,operations,admin}/{guides,reports,reference}
    
    local synced=0
    
    # === PUBLIC DOCUMENTS (All Users) ===
    log "📖 Syncing public documents..."
    
    # Project overview
    for doc in "README.md" "CONTRIBUTORS.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/public/guides/$(basename $doc)" "Public" "guides"
            ((synced++))
            info "✅ Public: $doc"
        fi
    done
    
    # Setup guides
    for doc in "WIKI_SETUP_GUIDE.md" "PROJECT_SETUP_GUIDE.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/public/guides/$(basename $doc)" "Public" "setup"
            ((synced++))
            info "✅ Setup: $doc"
        fi
    done
    
    # === DEVELOPER DOCUMENTS (Developers + Admins) ===
    log "👨‍💻 Syncing developer documents..."
    
    for doc in "CONFIGURATION_GUIDE.md" "SETUP_EXTERNAL_CONFIG.md" "NETWORK_ACCESS_SETUP.md" "SEED_DATA_DOCUMENTATION.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/developer/guides/$(basename $doc)" "Developer" "technical"
            ((synced++))
            info "✅ Developer: $doc"
        fi
    done
    
    # === QA DOCUMENTS (QA Team + Admins) ===
    log "🧪 Syncing QA documents..."
    
    for doc in "UAT_QUICK_START_GUIDE.md" "TEST_RESULTS_MANAGEMENT.md" "PRE_PROD_UAT_PLAN.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/qa/guides/$(basename $doc)" "QA" "testing"
            ((synced++))
            info "✅ QA Guide: $doc"
        fi
    done
    
    for doc in "UAT_PHASE1_COMPLETION_REPORT.md" "UAT_EXECUTION_REPORT.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/qa/reports/$(basename $doc)" "QA" "reports"
            ((synced++))
            info "✅ QA Report: $doc"
        fi
    done
    
    # === OPERATIONS DOCUMENTS (Ops Team + Admins) ===
    log "🏗 Syncing operations documents..."
    
    for doc in "DOCKER_UAT_EXECUTION_GUIDE.md" "TROUBLESHOOTING_KNOWLEDGE_TRACKER.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/operations/guides/$(basename $doc)" "Operations" "infrastructure"
            ((synced++))
            info "✅ Ops Guide: $doc"
        fi
    done
    
    for doc in "DOCKER_INFRASTRUCTURE_EXECUTIVE_SUMMARY.md" "DOCKER_OPTIMIZATION_REPORT.md" "SECURITY_CHECK_REPORT.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/operations/reports/$(basename $doc)" "Operations" "reports"
            ((synced++))
            info "✅ Ops Report: $doc"
        fi
    done
    
    # === ADMIN DOCUMENTS (Admins Only) ===
    log "👑 Syncing admin documents..."
    
    for doc in "EXECUTIVE_SUMMARY.md" "COMMIT_READINESS_SUMMARY.md" "GITIGNORE_UPDATE_SUMMARY.md"; do
        if [ -f "$doc" ]; then
            add_wiki_header "$doc" "$WIKI_DOCS_DIR/admin/reports/$(basename $doc)" "Admin" "management"
            ((synced++))
            info "✅ Admin: $doc"
        fi
    done
    
    # Create navigation indexes
    create_navigation_indexes
    
    log "✅ Document sync complete: $synced files processed"
    generate_sync_summary "$synced"
}

# Add wiki metadata header to documents
add_wiki_header() {
    local source="$1"
    local target="$2"
    local access_level="$3"
    local category="$4"
    
    local title=$(basename "$source" .md | sed 's/_/ /g' | sed 's/\b\w/\U&/g')
    local date_now=$(date -Iseconds)
    
    # Create target directory
    mkdir -p "$(dirname "$target")"
    
    # Add wiki metadata
    cat > "$target" << EOF
---
title: $title
description: Auto-synced from BizDir project documentation
published: true
date: $date_now
tags: [$category, $access_level, auto-sync]
editor: markdown
dateCreated: $date_now
---

# $title

> **Access Level**: $access_level  
> **Category**: $category  
> **Last Updated**: $(date)  
> **Auto-synced from**: \`$source\`

---

EOF
    
    # Append original content
    cat "$source" >> "$target"
}

# Create navigation index pages
create_navigation_indexes() {
    log "Creating navigation indexes..."
    
    local date_now=$(date -Iseconds)
    
    # Main wiki index
    cat > "$WIKI_DOCS_DIR/README.md" << EOF
---
title: BizDir Documentation Hub
description: Central documentation hub for the BizDir project
published: true
date: $date_now
tags: [index, navigation, main]
editor: markdown
---

# 📚 BizDir Documentation Hub

Welcome to the comprehensive documentation for the BizDir project. Documentation is organized by access level and category.

## 🌐 Public Documentation
*Accessible to all users*

### 📋 [Project Guides](public/guides/)
- Project overview and getting started
- Setup and installation guides
- Contributor information

---

## 👨‍💻 Developer Documentation
*Accessible to developers and administrators*

### 🔧 [Technical Guides](developer/guides/)
- Configuration and setup
- Development environment
- API documentation

---

## 🧪 QA Documentation
*Accessible to QA team and administrators*

### 📝 [Testing Guides](qa/guides/)
- Test procedures and UAT guides
- Quality assurance processes

### 📊 [QA Reports](qa/reports/)
- Test execution reports
- Quality metrics and results

---

## 🏗 Operations Documentation
*Accessible to operations team and administrators*

### ⚙️ [Infrastructure Guides](operations/guides/)
- Docker and deployment guides
- System troubleshooting

### 📈 [Operations Reports](operations/reports/)
- Infrastructure reports
- Performance and security reports

---

## 👑 Administrator Documentation
*Accessible to administrators only*

### 📋 [Management Reports](admin/reports/)
- Executive summaries
- Project status and readiness reports

---

## 🔄 Auto-Sync Information

This documentation is automatically synchronized from the project repository:
- **Last Sync**: $(date)
- **Sync Script**: \`sync-wiki-docs.sh\`
- **Log File**: \`logs/wiki-sync.log\`

To update documentation, modify the source markdown files in the project repository and run the sync script.

---

*Generated automatically by BizDir wiki sync system*
EOF

    # Create individual section indexes
    create_section_index "public" "Public Documentation" "Documentation accessible to all users"
    create_section_index "developer" "Developer Documentation" "Technical documentation for developers"
    create_section_index "qa" "QA Documentation" "Quality assurance and testing documentation"
    create_section_index "operations" "Operations Documentation" "Infrastructure and operations documentation"
    create_section_index "admin" "Administrator Documentation" "Management and administrative documentation"
}

create_section_index() {
    local section="$1"
    local title="$2" 
    local description="$3"
    local date_now=$(date -Iseconds)
    
    cat > "$WIKI_DOCS_DIR/$section/README.md" << EOF
---
title: $title
description: $description
published: true
date: $date_now
tags: [index, $section]
editor: markdown
---

# $title

$description

## 📁 Available Categories

EOF

    # List available subdirectories
    for subdir in "$WIKI_DOCS_DIR/$section"/*; do
        if [ -d "$subdir" ]; then
            local dirname=$(basename "$subdir")
            echo "### 📂 [$(echo $dirname | sed 's/\b\w/\U&/g')]($dirname/)" >> "$WIKI_DOCS_DIR/$section/README.md"
            
            # List files in each subdirectory
            if ls "$subdir"/*.md >/dev/null 2>&1; then
                for file in "$subdir"/*.md; do
                    if [ -f "$file" ] && [ "$(basename "$file")" != "README.md" ]; then
                        local filename=$(basename "$file" .md)
                        local title=$(echo "$filename" | sed 's/_/ /g' | sed 's/\b\w/\U&/g')
                        echo "- [$title]($dirname/$(basename "$file"))" >> "$WIKI_DOCS_DIR/$section/README.md"
                    fi
                done
            fi
            echo "" >> "$WIKI_DOCS_DIR/$section/README.md"
        fi
    done
    
    cat >> "$WIKI_DOCS_DIR/$section/README.md" << EOF

---
*This index is automatically generated*
EOF
}

# Generate sync summary
generate_sync_summary() {
    local synced_count="$1"
    local date_now=$(date -Iseconds)
    
    cat > "$WIKI_DOCS_DIR/sync-summary.md" << EOF
---
title: Documentation Sync Summary
description: Latest synchronization summary and statistics
published: true
date: $date_now
tags: [sync, summary, auto-generated]
editor: markdown
---

# 📊 Documentation Sync Summary

**Sync Date**: $(date)  
**Documents Processed**: $synced_count  
**Status**: ✅ Complete

## 📁 Organization Structure

- **📖 Public**: General documentation for all users
- **👨‍💻 Developer**: Technical documentation for development team
- **🧪 QA**: Testing and quality assurance documentation  
- **🏗 Operations**: Infrastructure and deployment documentation
- **👑 Admin**: Management and executive documentation

## 🔄 Sync Process

Documents are automatically categorized based on:
- **Content type** (guides, reports, reference)
- **Target audience** (public, developers, qa, operations, admin)
- **Access level** (public, team-specific, admin-only)

## 📋 Recent Activity

$(find "$WIKI_DOCS_DIR" -name "*.md" -type f | wc -l) total documentation files available.

---
*Auto-generated by sync-wiki-docs.sh on $(date)*
EOF

    info "📊 Sync summary created"
}

# Main execution
case "${1:-sync}" in
    sync)
        if curl -s -o /dev/null -w "%{http_code}" "http://localhost:3000" | grep -q "200\|302"; then
            log "✅ Wiki is accessible"
            sync_documents
        else
            error "❌ Wiki is not accessible at http://localhost:3000"
            exit 1
        fi
        ;;
    status)
        info "📊 Wiki Documentation Status"
        if [ -d "$WIKI_DOCS_DIR" ]; then
            echo "Documents: $(find "$WIKI_DOCS_DIR" -name "*.md" -type f | wc -l)"
            echo "Categories: $(find "$WIKI_DOCS_DIR" -type d -mindepth 1 -maxdepth 1 | wc -l)"
            echo "Last sync: $([ -f "$SYNC_LOG" ] && tail -1 "$SYNC_LOG" || echo "Never")"
        else
            echo "No wiki directory found. Run 'sync' first."
        fi
        ;;
    clean)
        warning "Removing all synced documentation..."
        rm -rf "$WIKI_DOCS_DIR"
        log "✅ Cleaned up wiki directory"
        ;;
    help|*)
        echo "BizDir Wiki Documentation Sync"
        echo ""
        echo "Usage: $0 {sync|status|clean|help}"
        echo ""
        echo "Commands:"
        echo "  sync   - Sync all project documentation to wiki"
        echo "  status - Show sync status and statistics"
        echo "  clean  - Remove all synced documentation"
        echo "  help   - Show this help"
        echo ""
        echo "Generated wiki structure:"
        echo "  📖 public/     - Public documentation"
        echo "  👨‍💻 developer/  - Developer documentation"
        echo "  🧪 qa/         - QA and testing documentation"
        echo "  🏗 operations/ - Infrastructure documentation"
        echo "  👑 admin/      - Administrative documentation"
        ;;
esac
