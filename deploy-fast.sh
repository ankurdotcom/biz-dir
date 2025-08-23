#!/bin/bash

# BizDir Docker Fast Deployment Script
# Optimized for development and quick deployments

set -e

echo "🚀 BizDir Fast Docker Deployment"
echo "==============================="

# Check if optimized dockerfile exists
if [ ! -f "docker/php/Dockerfile.optimized" ]; then
    echo "❌ Optimized Dockerfile not found. Please run optimization setup first."
    exit 1
fi

# Default to fast compose file
COMPOSE_FILE=${1:-docker-compose.fast.yml}
PROFILE=${2:-core}

echo "📋 Using compose file: $COMPOSE_FILE"
echo "🎯 Using profile: $PROFILE"

# Environment setup
if [ ! -f .env ]; then
    echo "⚙️  Creating environment file..."
    cat > .env << EOF
# BizDir Environment Configuration
APP_ENV=production
DOMAIN_NAME=localhost

# Database
DB_ROOT_PASSWORD=bizdir_root_2025!
DB_NAME=bizdir_production  
DB_USER=bizdir_user
DB_PASSWORD=bizdir_secure_2025!
DB_PORT=3306

# Redis
REDIS_PASSWORD=bizdir_redis_2025!
REDIS_PORT=6379

# Web Server
HTTP_PORT=80
HTTPS_PORT=443

# Optional Services
ELASTICSEARCH_PORT=9200
PROMETHEUS_PORT=9090
GRAFANA_PORT=3000
GRAFANA_PASSWORD=admin
EOF
    echo "✅ Environment file created"
fi

# Start deployment
echo "🏗️  Building and starting services..."

if [ "$PROFILE" = "core" ]; then
    # Core services only (fastest)
    docker compose -f $COMPOSE_FILE up -d --build
elif [ "$PROFILE" = "full" ]; then
    # Full services including search
    docker compose -f $COMPOSE_FILE --profile full up -d --build
elif [ "$PROFILE" = "monitoring" ]; then
    # With monitoring
    docker compose -f $COMPOSE_FILE --profile monitoring up -d --build
else
    echo "❌ Unknown profile: $PROFILE"
    echo "Available profiles: core, full, monitoring"
    exit 1
fi

# Wait for services
echo "⏳ Waiting for services to be ready..."
sleep 10

# Health check
echo "🔍 Checking service health..."
docker compose -f $COMPOSE_FILE ps

# Get container status
if docker compose -f $COMPOSE_FILE ps | grep -q "Up"; then
    echo "✅ Deployment successful!"
    echo ""
    echo "🌐 Access your application:"
    echo "   Web: http://localhost"
    echo "   Database: localhost:3306"
    echo "   Redis: localhost:6379"
    
    if [ "$PROFILE" = "monitoring" ]; then
        echo "   Grafana: http://localhost:3000 (admin/admin)"
        echo "   Prometheus: http://localhost:9090"
    fi
    
    if [ "$PROFILE" = "full" ]; then
        echo "   Elasticsearch: http://localhost:9200"
    fi
else
    echo "❌ Some services failed to start. Check logs:"
    echo "   docker compose -f $COMPOSE_FILE logs"
fi

echo ""
echo "📊 Quick Commands:"
echo "   View logs: docker compose -f $COMPOSE_FILE logs -f"
echo "   Stop: docker compose -f $COMPOSE_FILE down"
echo "   Rebuild: docker compose -f $COMPOSE_FILE up -d --build"
