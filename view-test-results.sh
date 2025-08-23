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
