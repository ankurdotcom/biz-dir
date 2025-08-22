#!/bin/bash

# BizDir Platform Health Monitor
LOG_FILE="/var/log/bizdir-monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Check web server status
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200"; then
    WEB_STATUS="OK"
else
    WEB_STATUS="FAILED"
fi

# Check database connectivity
if mysql -e "SELECT 1" $DB_NAME > /dev/null 2>&1; then
    DB_STATUS="OK"
else
    DB_STATUS="FAILED"
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    DISK_STATUS="WARNING (${DISK_USAGE}%)"
else
    DISK_STATUS="OK (${DISK_USAGE}%)"
fi

# Log status
echo "[$DATE] Web: $WEB_STATUS | DB: $DB_STATUS | Disk: $DISK_STATUS" >> $LOG_FILE

# Send alert if any service is down
if [ "$WEB_STATUS" != "OK" ] || [ "$DB_STATUS" != "OK" ]; then
    echo "ALERT: BizDir service issue detected at $DATE" | logger
fi
