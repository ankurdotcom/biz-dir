#!/bin/bash

# Setup External Test Results Directory
# This script sets up the external test results directory structure

set -e

# Configuration
EXTERNAL_DIR="/home/ankur/biz-dir-test-results"
PROJECT_DIR="/home/ankur/workspace/biz-dir"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Setting up External Test Results Directory${NC}"
echo "==============================================="

# Create external directory structure
echo -e "${BLUE}📁 Creating external directory structure...${NC}"
mkdir -p "$EXTERNAL_DIR"/{logs,results,coverage,screenshots,performance,artifacts,reports,archives}

# Set proper permissions
chmod 755 "$EXTERNAL_DIR"
chmod 755 "$EXTERNAL_DIR"/*

echo -e "${GREEN}✅ External directory created: $EXTERNAL_DIR${NC}"

# Create archive subdirectories
echo -e "${BLUE}📦 Creating archive structure...${NC}"
current_month=$(date +"%Y-%m")
mkdir -p "$EXTERNAL_DIR/archives/$current_month"

# Create environment file for easy access
echo -e "${BLUE}🌍 Creating environment configuration...${NC}"
cat > "$PROJECT_DIR/.env.testing" << EOF
# External Test Results Configuration
# Source this file in your shell or CI/CD pipeline

export BIZDIR_TEST_RESULTS_DIR="$EXTERNAL_DIR"
export BIZDIR_TEST_LOGS_DIR="$EXTERNAL_DIR/logs"
export BIZDIR_TEST_COVERAGE_DIR="$EXTERNAL_DIR/coverage"
export BIZDIR_TEST_REPORTS_DIR="$EXTERNAL_DIR/reports"
export BIZDIR_TEST_ARTIFACTS_DIR="$EXTERNAL_DIR/artifacts"

# Aliases for easy navigation
alias bizdir-test-results="cd $EXTERNAL_DIR"
alias bizdir-test-logs="cd $EXTERNAL_DIR/logs"
alias bizdir-test-coverage="cd $EXTERNAL_DIR/coverage"
alias bizdir-latest-results="find $EXTERNAL_DIR/results -name '*.html' -type f -printf '%T@ %p\n' | sort -k 1nr | head -5"
alias bizdir-latest-coverage="find $EXTERNAL_DIR/coverage -name 'index.html' -type f -printf '%T@ %p\n' | sort -k 1nr | head -1"
EOF

# Create quick access script
echo -e "${BLUE}🚀 Creating quick access script...${NC}"
cat > "$PROJECT_DIR/view-test-results.sh" << 'EOF'
#!/bin/bash

# Quick Test Results Viewer
RESULTS_DIR="/home/ankur/biz-dir-test-results"

echo "🔍 BizDir Test Results Viewer"
echo "============================="

# Find latest results
echo "📊 Latest Test Results:"
find "$RESULTS_DIR/results" -name "*.html" -type f -printf '%T@ %p\n' | sort -k 1nr | head -5 | while read time file; do
    filename=$(basename "$file")
    timestamp=$(date -d "@$time" "+%Y-%m-%d %H:%M:%S")
    echo "  $timestamp - $filename"
done

echo ""
echo "📈 Latest Coverage Reports:"
find "$RESULTS_DIR/coverage" -name "index.html" -type f -printf '%T@ %p\n' | sort -k 1nr | head -3 | while read time file; do
    filename=$(dirname "$file" | xargs basename)
    timestamp=$(date -d "@$time" "+%Y-%m-%d %H:%M:%S")
    echo "  $timestamp - $filename"
done

echo ""
echo "📝 Recent Log Files:"
find "$RESULTS_DIR/logs" -name "*.log" -type f -printf '%T@ %p\n' | sort -k 1nr | head -5 | while read time file; do
    filename=$(basename "$file")
    timestamp=$(date -d "@$time" "+%Y-%m-%d %H:%M:%S")
    echo "  $timestamp - $filename"
done

echo ""
echo "🌐 Quick Actions:"
echo "  Open latest test results:  xdg-open \$(find $RESULTS_DIR/results -name '*.html' -type f -printf '%T@ %p\n' | sort -k 1nr | head -1 | cut -d' ' -f2-)"
echo "  Open latest coverage:      xdg-open \$(find $RESULTS_DIR/coverage -name 'index.html' -type f -printf '%T@ %p\n' | sort -k 1nr | head -1 | cut -d' ' -f2-)"
echo "  Browse results directory:  nautilus $RESULTS_DIR"
EOF

chmod +x "$PROJECT_DIR/view-test-results.sh"

# Create cleanup script
echo -e "${BLUE}🧹 Creating cleanup script...${NC}"
cat > "$PROJECT_DIR/cleanup-test-results.sh" << 'EOF'
#!/bin/bash

# Test Results Cleanup Script
RESULTS_DIR="/home/ankur/biz-dir-test-results"
DAYS_TO_KEEP=${1:-7}

echo "🧹 Cleaning up test results older than $DAYS_TO_KEEP days..."

# Archive old results
archive_dir="$RESULTS_DIR/archives/$(date +%Y-%m)"
mkdir -p "$archive_dir"

# Move old files to archive
find "$RESULTS_DIR/logs" -type f -mtime +$DAYS_TO_KEEP -exec mv {} "$archive_dir/" \; 2>/dev/null || true
find "$RESULTS_DIR/results" -type f -mtime +$DAYS_TO_KEEP -exec mv {} "$archive_dir/" \; 2>/dev/null || true

# Keep only latest 3 coverage reports
cd "$RESULTS_DIR/coverage"
ls -t | tail -n +4 | xargs rm -rf 2>/dev/null || true

echo "✅ Cleanup completed. Archived files moved to: $archive_dir"
EOF

chmod +x "$PROJECT_DIR/cleanup-test-results.sh"

# Verify the setup
echo -e "${BLUE}✅ Verifying setup...${NC}"
if [[ -d "$EXTERNAL_DIR" ]]; then
    echo -e "${GREEN}✅ External directory exists: $EXTERNAL_DIR${NC}"
else
    echo -e "${RED}❌ Failed to create external directory${NC}"
    exit 1
fi

# Check directory structure
required_dirs=("logs" "results" "coverage" "artifacts" "reports" "archives")
for dir in "${required_dirs[@]}"; do
    if [[ -d "$EXTERNAL_DIR/$dir" ]]; then
        echo -e "${GREEN}✅ $dir directory created${NC}"
    else
        echo -e "${RED}❌ Failed to create $dir directory${NC}"
    fi
done

# Test write permissions
test_file="$EXTERNAL_DIR/test_write_access"
if touch "$test_file" 2>/dev/null; then
    rm "$test_file"
    echo -e "${GREEN}✅ Write permissions verified${NC}"
else
    echo -e "${RED}❌ No write permissions to external directory${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 External test results directory setup completed!${NC}"
echo ""
echo "📂 Directory Structure:"
echo "  $EXTERNAL_DIR/"
echo "  ├── logs/                 # Test execution logs"
echo "  ├── results/              # Test result files (HTML, XML, JSON)"
echo "  ├── coverage/             # Code coverage reports"
echo "  ├── screenshots/          # UI test screenshots"
echo "  ├── performance/          # Performance test data"
echo "  ├── artifacts/            # Test artifacts and temporary files"
echo "  ├── reports/              # Generated test reports"
echo "  └── archives/             # Archived old test runs"
echo ""
echo "🔧 Configuration Files Created:"
echo "  $PROJECT_DIR/.env.testing           # Environment variables"
echo "  $PROJECT_DIR/view-test-results.sh   # Quick results viewer"
echo "  $PROJECT_DIR/cleanup-test-results.sh # Cleanup script"
echo ""
echo "🚀 Next Steps:"
echo "  1. Source the environment file: source .env.testing"
echo "  2. Run tests: ./mvp/run-tests.sh"
echo "  3. View results: ./view-test-results.sh"
echo "  4. Cleanup old results: ./cleanup-test-results.sh"
echo ""
echo -e "${YELLOW}💡 Tip: Add 'source \$PROJECT_DIR/.env.testing' to your ~/.bashrc for permanent environment setup${NC}"
