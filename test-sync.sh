#!/bin/bash
#
# Simple Wiki Document Sync Test
#

set -e

# Configuration
WIKI_DOCS_DIR="./wiki"
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

# Test sync of a few key documents
test_sync() {
    log "Testing simple document sync..."
    
    # Create structure
    mkdir -p "$WIKI_DOCS_DIR"/{public,developer,qa,operations,admin}
    
    # Copy a few files to test
    if [ -f "README.md" ]; then
        cp "README.md" "$WIKI_DOCS_DIR/public/"
        log "✅ Synced README.md"
    fi
    
    if [ -f "CONFIGURATION_GUIDE.md" ]; then
        cp "CONFIGURATION_GUIDE.md" "$WIKI_DOCS_DIR/developer/"
        log "✅ Synced CONFIGURATION_GUIDE.md"
    fi
    
    if [ -f "UAT_QUICK_START_GUIDE.md" ]; then
        cp "UAT_QUICK_START_GUIDE.md" "$WIKI_DOCS_DIR/qa/"
        log "✅ Synced UAT_QUICK_START_GUIDE.md"
    fi
    
    log "✅ Test sync complete!"
    log "📁 Check ./wiki/ directory for synced files"
}

test_sync
