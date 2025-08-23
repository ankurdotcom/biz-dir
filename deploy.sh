#!/bin/bash

# Business Directory Production Deployment Script
# This script helps deploy the Business Directory application using Docker Compose

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! docker compose version &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    log_success "Docker and Docker Compose are installed."
}

# Check if .env file exists
check_env_file() {
    if [ ! -f .env ]; then
        log_warning ".env file not found. Creating from .env.example..."
        cp .env.example .env
        log_info "Please edit .env file with your configuration before continuing."
        read -p "Press Enter when you're ready to continue..."
    fi
}

# Create necessary directories
create_directories() {
    log_info "Creating necessary directories..."
    
    directories=(
        "data/mysql"
        "data/redis"
        "data/elasticsearch"
        "data/prometheus"
        "data/grafana"
        "logs/nginx"
        "logs/php"
        "logs/mysql"
        "logs/redis"
        "backups"
        "ssl"
    )
    
    for dir in "${directories[@]}"; do
        mkdir -p "$dir"
        log_info "Created directory: $dir"
    done
    
    log_success "Directories created successfully."
}

# Set proper permissions
set_permissions() {
    log_info "Setting proper permissions..."
    
    # WordPress data directory
    sudo chown -R www-data:www-data data/ || true
    
    # Log directories
    sudo chmod -R 755 logs/ || true
    
    # Backup directory
    sudo chmod -R 755 backups/ || true
    
    log_success "Permissions set successfully."
}

# Build Docker images
build_images() {
    log_info "Building Docker images..."
    docker compose build --no-cache
    log_success "Docker images built successfully."
}

# Start services
start_services() {
    log_info "Starting services..."
    
    # Start core services first
    docker compose up -d db redis
    log_info "Database and Redis started. Waiting for them to be ready..."
    sleep 30
    
    # Start application services
    docker compose up -d php nginx
    log_info "PHP and Nginx started. Waiting for application to be ready..."
    sleep 20
    
    # Start optional services
    if [ "$1" = "full" ]; then
        log_info "Starting optional services (Elasticsearch, Monitoring, Backup)..."
        docker compose up -d elasticsearch prometheus grafana backup
        sleep 10
    fi
    
    log_success "Services started successfully."
}

# Check service health
check_health() {
    log_info "Checking service health..."
    
    services=("db" "redis" "php" "nginx")
    
    for service in "${services[@]}"; do
        if docker compose ps "$service" | grep -q "Up (healthy)"; then
            log_success "$service is healthy"
        else
            log_warning "$service may not be ready yet"
        fi
    done
}

# Display URLs and information
display_info() {
    log_info "Deployment completed! Here's your setup information:"
    echo ""
    echo "🌐 Application URLs:"
    echo "   WordPress: http://localhost"
    echo "   WordPress Admin: http://localhost/wp-admin"
    echo ""
    echo "📊 Monitoring (if enabled):"
    echo "   Grafana: http://localhost:3000 (admin/admin123!)"
    echo "   Prometheus: http://localhost:9090"
    echo ""
    echo "🔧 Database Access:"
    echo "   MySQL: localhost:3306"
    echo "   Redis: localhost:6379"
    echo ""
    echo "📝 Useful Commands:"
    echo "   View logs: docker-compose logs -f [service]"
    echo "   Stop all: docker-compose down"
    echo "   Restart: docker-compose restart [service]"
    echo "   Shell access: docker-compose exec [service] /bin/bash"
    echo ""
}

# Backup function
backup_data() {
    log_info "Creating backup..."
    
    timestamp=$(date +%Y%m%d_%H%M%S)
    backup_dir="backups/backup_$timestamp"
    
    mkdir -p "$backup_dir"
    
    # Backup database
    docker compose exec -T db mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --all-databases > "$backup_dir/database.sql"
    
    # Backup WordPress files
    docker compose exec -T php tar -czf - /var/www/html > "$backup_dir/wordpress.tar.gz"
    
    log_success "Backup created: $backup_dir"
}

# Restore function
restore_data() {
    backup_dir="$1"
    
    if [ ! -d "$backup_dir" ]; then
        log_error "Backup directory not found: $backup_dir"
        exit 1
    fi
    
    log_info "Restoring from backup: $backup_dir"
    
    # Restore database
    if [ -f "$backup_dir/database.sql" ]; then
        docker compose exec -T db mysql -u root -p"$MYSQL_ROOT_PASSWORD" < "$backup_dir/database.sql"
        log_success "Database restored"
    fi
    
    # Restore WordPress files
    if [ -f "$backup_dir/wordpress.tar.gz" ]; then
        docker compose exec -T php tar -xzf - -C / < "$backup_dir/wordpress.tar.gz"
        log_success "WordPress files restored"
    fi
}

# Main script
main() {
    echo "🚀 Business Directory Production Deployment"
    echo "=========================================="
    echo ""
    
    case "$1" in
        "deploy")
            check_docker
            check_env_file
            create_directories
            set_permissions
            build_images
            start_services "$2"
            sleep 10
            check_health
            display_info
            ;;
        "start")
            start_services "$2"
            check_health
            display_info
            ;;
        "stop")
            log_info "Stopping all services..."
            docker compose down
            log_success "All services stopped"
            ;;
        "restart")
            log_info "Restarting services..."
            docker compose restart
            log_success "Services restarted"
            ;;
        "logs")
            service="${2:-}"
            if [ -n "$service" ]; then
                docker compose logs -f "$service"
            else
                docker compose logs -f
            fi
            ;;
        "backup")
            backup_data
            ;;
        "restore")
            if [ -z "$2" ]; then
                log_error "Please specify backup directory"
                exit 1
            fi
            restore_data "$2"
            ;;
        "status")
            docker compose ps
            ;;
        "shell")
            service="${2:-php}"
            docker compose exec "$service" /bin/bash
            ;;
        *)
            echo "Usage: $0 {deploy|start|stop|restart|logs|backup|restore|status|shell}"
            echo ""
            echo "Commands:"
            echo "  deploy [full]  - Full deployment (use 'full' for all services)"
            echo "  start [full]   - Start services"
            echo "  stop           - Stop all services"
            echo "  restart        - Restart all services"
            echo "  logs [service] - View logs"
            echo "  backup         - Create backup"
            echo "  restore <dir>  - Restore from backup"
            echo "  status         - Show service status"
            echo "  shell [service] - Access service shell (default: php)"
            echo ""
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"
