#!/bin/bash

# BizDir Platform Backup Script
BACKUP_DIR="/var/backups/bizdir"
DATE=$(date +%Y%m%d_%H%M%S)
SITE_DIR="/var/www/html/bizdir"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump $DB_NAME > $BACKUP_DIR/database_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz -C $SITE_DIR .

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
