#!/bin/bash

# Add ABSPATH protection to PHP files
# BizDir Security Enhancement Script
# Version: 1.0
# Date: August 23, 2025

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE} BizDir Security Enhancement: ABSPATH Protection${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

PLUGIN_DIR="/home/ankur/workspace/biz-dir/mvp/wp-content/plugins/biz-dir-core"
FILES_PROCESSED=0
FILES_UPDATED=0

# Function to add ABSPATH protection to a file
add_abspath_protection() {
    local file="$1"
    local relative_path="${file#$PLUGIN_DIR/}"
    
    echo -e "${BLUE}Checking: $relative_path${NC}"
    
    # Check if file already has ABSPATH protection
    if grep -q "ABSPATH" "$file"; then
        echo -e "${GREEN}  ✅ Already protected${NC}"
        return 0
    fi
    
    # Check if it's a PHP file with opening tag
    if ! grep -q "<?php" "$file"; then
        echo -e "${YELLOW}  ⚠️ Not a PHP file, skipping${NC}"
        return 0
    fi
    
    # Create backup
    cp "$file" "$file.backup"
    
    # Create temporary file with ABSPATH protection
    {
        # Get the first line (should be <?php)
        head -n 1 "$file"
        
        # Add the protection
        echo "/**"
        echo " * Prevent direct access"
        echo " */"
        echo "if (!defined('ABSPATH')) {"
        echo "    exit;"
        echo "}"
        echo ""
        
        # Add the rest of the file (skip first line)
        tail -n +2 "$file"
    } > "$file.tmp"
    
    # Replace original file
    mv "$file.tmp" "$file"
    
    echo -e "${GREEN}  ✅ Protection added${NC}"
    FILES_UPDATED=$((FILES_UPDATED + 1))
}

# Find all PHP files in the plugin
echo -e "${BLUE}Scanning for PHP files in plugin directory...${NC}"

while IFS= read -r -d '' file; do
    FILES_PROCESSED=$((FILES_PROCESSED + 1))
    add_abspath_protection "$file"
done < <(find "$PLUGIN_DIR/includes" -name "*.php" -type f -print0)

echo ""
echo -e "${BLUE}==================================================${NC}"
echo -e "${GREEN}Security Enhancement Complete!${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""
echo -e "${YELLOW}Summary:${NC}"
echo -e "Files processed: $FILES_PROCESSED"
echo -e "Files updated: $FILES_UPDATED"
echo -e "Files already protected: $((FILES_PROCESSED - FILES_UPDATED))"
echo ""

if [ $FILES_UPDATED -gt 0 ]; then
    echo -e "${GREEN}✅ $FILES_UPDATED files have been secured with ABSPATH protection${NC}"
    echo -e "${YELLOW}Backup files created with .backup extension${NC}"
else
    echo -e "${GREEN}✅ All files were already protected${NC}"
fi

echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "1. Test the updated files"
echo -e "2. Re-run UAT validation"
echo -e "3. Remove backup files when confirmed working"
echo ""
