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
