#!/bin/bash

echo "=== Setting up Redis Monitoring ==="

# Create log directory
sudo mkdir -p /var/log/redis-monitor
sudo chown www-data:www-data /var/log/redis-monitor

# Create monitoring script in /usr/local/bin
sudo cp monitor-redis.sh /usr/local/bin/monitor-redis.sh
sudo chmod +x /usr/local/bin/monitor-redis.sh

# Create alert script
cat << 'EOF' | sudo tee /usr/local/bin/redis-alert.sh
#!/bin/bash

MEMORY_USAGE=$(redis-cli info memory | grep used_memory_human | cut -d: -f2 | tr -d '\r' | sed 's/[A-Z]//g')
MAX_MEMORY=$(redis-cli config get maxmemory | tail -1)

# Convert to MB for comparison
if [[ $MAX_MEMORY =~ mb$ ]]; then
    MAX_MB=${MAX_MEMORY%mb}
elif [[ $MAX_MEMORY =~ gb$ ]]; then
    MAX_GB=${MAX_MEMORY%gb}
    MAX_MB=$((MAX_GB * 1024))
else
    MAX_MB=$((MAX_MEMORY / 1024 / 1024))
fi

CURRENT_MB=${MEMORY_USAGE%.*}

# Alert if usage > 80%
THRESHOLD=$((MAX_MB * 80 / 100))

if [ "$CURRENT_MB" -gt "$THRESHOLD" ]; then
    echo "[$(date)] WARNING: Redis memory usage is ${CURRENT_MB}MB (${MAX_MB}MB max)" >> /var/log/redis-monitor/alerts.log
    
    # Clear Laravel cache to free up Redis memory
    cd /var/www/html/e-retribusi-api && php artisan cache:clear >> /var/log/redis-monitor/alerts.log 2>&1
fi
EOF

sudo chmod +x /usr/local/bin/redis-alert.sh

# Add to crontab (run every 5 minutes)
CRON_JOB="*/5 * * * * /usr/local/bin/redis-alert.sh"
(crontab -l 2>/dev/null | grep -v redis-alert.sh; echo "$CRON_JOB") | crontab -

echo ""
echo "✅ Redis monitoring setup complete!"
echo ""
echo "Monitoring script: /usr/local/bin/monitor-redis.sh"
echo "Alert script: /usr/local/bin/redis-alert.sh"
echo "Logs: /var/log/redis-monitor/"
echo ""
echo "Cron job added: Check Redis every 5 minutes"
echo "Auto-clear cache if memory usage > 80%"
