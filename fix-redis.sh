#!/bin/bash

echo "=== Fixing Redis Configuration ==="

# Backup redis.conf
sudo cp /etc/redis/redis.conf /etc/redis/redis.conf.backup.$(date +%Y%m%d_%H%M%S)

# Set maxmemory to 256MB (adjust based on your server RAM)
sudo sed -i 's/^maxmemory .*/maxmemory 256mb/' /etc/redis/redis.conf
if ! grep -q "^maxmemory" /etc/redis/redis.conf; then
    echo "maxmemory 256mb" | sudo tee -a /etc/redis/redis.conf
fi

# Set maxmemory-policy to allkeys-lru (removes least recently used keys)
sudo sed -i 's/^maxmemory-policy .*/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
if ! grep -q "^maxmemory-policy" /etc/redis/redis.conf; then
    echo "maxmemory-policy allkeys-lru" | sudo tee -a /etc/redis/redis.conf
fi

# Enable persistence (optional but recommended)
sudo sed -i 's/^save .*/save 900 1\nsave 300 10\nsave 60 10000/' /etc/redis/redis.conf

echo ""
echo "=== Configuration Applied ==="
echo "maxmemory: 256mb"
echo "maxmemory-policy: allkeys-lru"
echo ""

# Restart Redis and Apache
echo "Restarting Redis..."
sudo systemctl restart redis

echo "Restarting Apache..."
sudo systemctl restart apache2 2>/dev/null || sudo service apache2 restart

# Wait for Redis to start
sleep 2

# Verify configuration
echo ""
echo "=== Verifying Redis Configuration ==="
redis-cli config get maxmemory
redis-cli config get maxmemory-policy

echo ""
echo "=== Redis Info ==="
redis-cli info memory | grep -E "used_memory_human|maxmemory"

echo ""
echo "✅ Redis configuration fixed!"
echo ""
echo "IMPORTANT: Monitor your Redis memory usage with:"
echo "  redis-cli info memory"
