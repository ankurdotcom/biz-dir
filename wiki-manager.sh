#!/bin/bash
#
# Wiki Application Setup and Management Script
# Sets up and manages the BizDir Wiki application
#

set -e

# Configuration
WIKI_COMPOSE_FILE="docker-compose-wiki.yml"
WIKI_URL="http://localhost:3000"
ADMINER_URL="http://localhost:8080"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        error "Docker is not running. Please start Docker first."
        exit 1
    fi
}

# Start the wiki application
start_wiki() {
    log "Starting BizDir Wiki Application..."
    
    check_docker
    
    if [ ! -f "$WIKI_COMPOSE_FILE" ]; then
        error "Wiki compose file not found: $WIKI_COMPOSE_FILE"
        exit 1
    fi
    
    log "Starting containers..."
    docker compose -f "$WIKI_COMPOSE_FILE" up -d
    
    log "Waiting for services to be ready..."
    sleep 10
    
    # Check if services are running
    if docker compose -f "$WIKI_COMPOSE_FILE" ps | grep -q "Up"; then
        log "✅ Wiki services started successfully!"
        echo
        info "🌐 Wiki Application: $WIKI_URL"
        info "🔧 Database Admin: $ADMINER_URL"
        echo
        info "📖 Setup Instructions:"
        echo "   1. Open $WIKI_URL in your browser"
        echo "   2. Complete the initial setup wizard"
        echo "   3. Create your admin account"
        echo "   4. Start creating your wiki pages!"
        echo
        info "🔑 Database Connection (for Adminer):"
        echo "   Server: wiki_db"
        echo "   Username: wiki_user"
        echo "   Password: wiki_secure_2025!"
        echo "   Database: wiki_db"
    else
        error "Failed to start wiki services"
        docker compose -f "$WIKI_COMPOSE_FILE" logs
        exit 1
    fi
}

# Stop the wiki application
stop_wiki() {
    log "Stopping BizDir Wiki Application..."
    docker compose -f "$WIKI_COMPOSE_FILE" down
    log "✅ Wiki services stopped"
}

# Restart the wiki application
restart_wiki() {
    log "Restarting BizDir Wiki Application..."
    stop_wiki
    sleep 2
    start_wiki
}

# Show status of wiki services
status_wiki() {
    log "BizDir Wiki Application Status:"
    docker compose -f "$WIKI_COMPOSE_FILE" ps
    echo
    
    # Check if wiki is accessible
    if curl -s -o /dev/null -w "%{http_code}" "$WIKI_URL" | grep -q "200\|302"; then
        info "✅ Wiki is accessible at: $WIKI_URL"
    else
        warning "❌ Wiki is not responding at: $WIKI_URL"
    fi
    
    if curl -s -o /dev/null -w "%{http_code}" "$ADMINER_URL" | grep -q "200"; then
        info "✅ Adminer is accessible at: $ADMINER_URL"
    else
        warning "❌ Adminer is not responding at: $ADMINER_URL"
    fi
}

# Show logs
logs_wiki() {
    log "Showing BizDir Wiki logs..."
    docker compose -f "$WIKI_COMPOSE_FILE" logs -f
}

# Reset wiki (removes all data)
reset_wiki() {
    warning "This will DELETE ALL wiki data. Are you sure? (y/N)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        log "Resetting BizDir Wiki..."
        docker compose -f "$WIKI_COMPOSE_FILE" down -v
        log "✅ Wiki reset complete. Run 'start' to begin fresh setup."
    else
        info "Reset cancelled."
    fi
}

# Backup wiki data
backup_wiki() {
    log "Creating backup of wiki data..."
    
    BACKUP_DIR="backups/wiki-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    docker compose -f "$WIKI_COMPOSE_FILE" exec -T wiki_db mysqldump \
        -u wiki_user -pwiki_secure_2025! wiki_db > "$BACKUP_DIR/wiki_database.sql"
    
    # Backup wiki data volumes
    docker run --rm \
        -v bizdir_wiki_data:/data \
        -v "$(pwd)/$BACKUP_DIR":/backup \
        alpine tar czf /backup/wiki_data.tar.gz -C /data .
    
    log "✅ Backup created: $BACKUP_DIR"
}

# Show help
show_help() {
    echo "BizDir Wiki Management Script"
    echo
    echo "Usage: $0 {start|stop|restart|status|logs|reset|backup|help}"
    echo
    echo "Commands:"
    echo "  start    - Start the wiki application"
    echo "  stop     - Stop the wiki application"
    echo "  restart  - Restart the wiki application"
    echo "  status   - Show status of wiki services"
    echo "  logs     - Show and follow wiki logs"
    echo "  reset    - Reset wiki (DELETE ALL DATA)"
    echo "  backup   - Create backup of wiki data"
    echo "  help     - Show this help message"
    echo
    echo "URLs:"
    echo "  Wiki:    $WIKI_URL"
    echo "  Adminer: $ADMINER_URL"
}

# Main script logic
case "${1:-help}" in
    start)
        start_wiki
        ;;
    stop)
        stop_wiki
        ;;
    restart)
        restart_wiki
        ;;
    status)
        status_wiki
        ;;
    logs)
        logs_wiki
        ;;
    reset)
        reset_wiki
        ;;
    backup)
        backup_wiki
        ;;
    help|*)
        show_help
        ;;
esac
