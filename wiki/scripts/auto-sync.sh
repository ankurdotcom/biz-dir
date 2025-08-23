#!/bin/bash

# BizDir Wiki Auto-Sync Script
# This script monitors the main BizDir project for changes and updates the wiki documentation

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
WIKI_DIR="$PROJECT_ROOT/wiki"
LOG_FILE="$WIKI_DIR/logs/sync.log"

# Ensure log directory exists
mkdir -p "$WIKI_DIR/logs"

# Logging function
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Error handling
error_exit() {
    log "ERROR: $1"
    exit 1
}

# Check if wiki system is properly installed
check_wiki_setup() {
    log "Checking wiki system setup..."
    
    if [ ! -f "$WIKI_DIR/config/config.php" ]; then
        error_exit "Wiki config file not found. Please run setup.php first."
    fi
    
    if [ ! -f "$WIKI_DIR/composer.lock" ]; then
        error_exit "Composer dependencies not installed. Please run 'composer install' in wiki directory."
    fi
    
    log "Wiki system setup verified."
}

# Monitor main project for changes
monitor_project_changes() {
    log "Starting project monitoring for auto-sync..."
    
    # Use inotify to watch for file changes in the main project
    inotifywait -m -r -e modify,create,delete,move \
        --exclude '\.git|node_modules|vendor|\.DS_Store|\.log$|\.tmp$' \
        "$PROJECT_ROOT/mvp" \
        "$PROJECT_ROOT/prompt" \
        "$PROJECT_ROOT"/*.md \
        "$PROJECT_ROOT"/*.json 2>/dev/null | while read path action file; do
        
        log "Detected change: $action on $path$file"
        
        # Trigger sync based on file type
        case "$file" in
            *.php)
                sync_code_documentation "$path$file"
                ;;
            *.md)
                sync_markdown_content "$path$file"
                ;;
            *.json)
                sync_configuration_docs "$path$file"
                ;;
            *.sql)
                sync_database_schema "$path$file"
                ;;
            *)
                log "Ignoring change to $file (not monitored file type)"
                ;;
        esac
        
        # Update executive dashboard metrics
        update_executive_metrics
        
    done
}

# Sync code documentation
sync_code_documentation() {
    local file_path="$1"
    log "Syncing code documentation for: $file_path"
    
    # Extract relevant information from PHP files
    if [[ "$file_path" == *.php ]]; then
        # Generate API documentation
        php "$WIKI_DIR/scripts/generate_api_docs.php" "$file_path"
        
        # Update code coverage reports
        update_code_coverage
    fi
}

# Sync markdown content
sync_markdown_content() {
    local file_path="$1"
    log "Syncing markdown content: $file_path"
    
    # Import markdown files directly into wiki
    php "$WIKI_DIR/scripts/import_markdown.php" "$file_path"
}

# Sync configuration documentation
sync_configuration_docs() {
    local file_path="$1"
    log "Syncing configuration docs: $file_path"
    
    # Parse configuration files and update documentation
    php "$WIKI_DIR/scripts/update_config_docs.php" "$file_path"
}

# Sync database schema documentation
sync_database_schema() {
    local file_path="$1"
    log "Syncing database schema: $file_path"
    
    # Generate ER diagrams and schema documentation
    php "$WIKI_DIR/scripts/generate_schema_docs.php" "$file_path"
}

# Update executive dashboard metrics
update_executive_metrics() {
    log "Updating executive dashboard metrics..."
    
    # Calculate project metrics
    local total_files=$(find "$PROJECT_ROOT/mvp" -name "*.php" | wc -l)
    local total_lines=$(find "$PROJECT_ROOT/mvp" -name "*.php" -exec wc -l {} + | tail -1 | awk '{print $1}')
    local test_files=$(find "$PROJECT_ROOT/mvp/tests" -name "*Test.php" | wc -l)
    local last_commit=$(cd "$PROJECT_ROOT" && git log -1 --format="%H %s" 2>/dev/null || echo "No git repository")
    
    # Update metrics in database
    php "$WIKI_DIR/scripts/update_metrics.php" \
        --total-files="$total_files" \
        --total-lines="$total_lines" \
        --test-files="$test_files" \
        --last-commit="$last_commit"
}

# Update code coverage
update_code_coverage() {
    log "Updating code coverage metrics..."
    
    if [ -f "$PROJECT_ROOT/mvp/phpunit.xml" ]; then
        cd "$PROJECT_ROOT/mvp"
        
        # Run PHPUnit with coverage if available
        if command -v phpunit &> /dev/null; then
            phpunit --coverage-text --coverage-html="$WIKI_DIR/public/coverage" > /tmp/coverage.log 2>&1
            
            # Extract coverage percentage
            local coverage=$(grep "Lines:" /tmp/coverage.log | tail -1 | grep -oP '\d+\.\d+%' || echo "0%")
            
            # Update coverage in wiki database
            php "$WIKI_DIR/scripts/update_coverage.php" --coverage="$coverage"
            
            log "Code coverage updated: $coverage"
        fi
    fi
}

# Generate comprehensive project report
generate_project_report() {
    log "Generating comprehensive project report..."
    
    # Run document generator
    php "$WIKI_DIR/scripts/generate_full_report.php"
    
    log "Project report generated successfully."
}

# Backup wiki database
backup_wiki_database() {
    local backup_dir="$WIKI_DIR/backups"
    local timestamp=$(date '+%Y%m%d_%H%M%S')
    local backup_file="$backup_dir/wiki_backup_$timestamp.sql"
    
    mkdir -p "$backup_dir"
    
    # Get database credentials from config
    local db_config=$(php -r "
        require_once '$WIKI_DIR/config/config.php';
        echo \$config['database']['host'] . '|' . 
             \$config['database']['database'] . '|' . 
             \$config['database']['username'] . '|' . 
             \$config['database']['password'];
    ")
    
    IFS='|' read -r db_host db_name db_user db_pass <<< "$db_config"
    
    # Create backup
    if command -v mysqldump &> /dev/null; then
        MYSQL_PWD="$db_pass" mysqldump -h"$db_host" -u"$db_user" "$db_name" > "$backup_file"
        
        if [ $? -eq 0 ]; then
            log "Wiki database backed up to: $backup_file"
            
            # Keep only last 10 backups
            ls -t "$backup_dir"/wiki_backup_*.sql | tail -n +11 | xargs -r rm
        else
            log "ERROR: Failed to backup wiki database"
        fi
    else
        log "WARNING: mysqldump not available, skipping database backup"
    fi
}

# Send notifications to configured channels
send_notification() {
    local message="$1"
    log "Sending notification: $message"
    
    # Send notification through wiki system
    php "$WIKI_DIR/scripts/send_notification.php" --message="$message" --type="system"
}

# Main function
main() {
    log "Starting BizDir Wiki Auto-Sync System"
    
    # Check prerequisites
    check_wiki_setup
    
    # Handle command line arguments
    case "${1:-monitor}" in
        "monitor")
            log "Starting continuous monitoring mode..."
            monitor_project_changes
            ;;
        "sync-once")
            log "Running one-time synchronization..."
            generate_project_report
            update_executive_metrics
            send_notification "Manual sync completed successfully"
            ;;
        "backup")
            log "Creating wiki database backup..."
            backup_wiki_database
            ;;
        "coverage")
            log "Updating code coverage..."
            update_code_coverage
            ;;
        "metrics")
            log "Updating executive metrics..."
            update_executive_metrics
            ;;
        "help")
            echo "BizDir Wiki Auto-Sync Script"
            echo ""
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  monitor     Start continuous monitoring (default)"
            echo "  sync-once   Run one-time synchronization"
            echo "  backup      Create database backup"
            echo "  coverage    Update code coverage metrics"
            echo "  metrics     Update executive dashboard metrics"
            echo "  help        Show this help message"
            echo ""
            exit 0
            ;;
        *)
            error_exit "Unknown command: $1. Use 'help' for usage information."
            ;;
    esac
}

# Install signal handlers for graceful shutdown
trap 'log "Received shutdown signal, stopping auto-sync..."; exit 0' SIGTERM SIGINT

# Ensure script is not already running
PIDFILE="$WIKI_DIR/logs/autosync.pid"
if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
    error_exit "Auto-sync script is already running (PID: $(cat $PIDFILE))"
fi

# Write PID file
echo $$ > "$PIDFILE"

# Clean up PID file on exit
trap 'rm -f "$PIDFILE"' EXIT

# Run main function
main "$@"
