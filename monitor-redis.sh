#!/bin/bash

echo "=== Redis Health Check ==="
echo ""

# Check if Redis is running
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis is running"
else
    echo "❌ Redis is DOWN!"
    exit 1
fi

echo ""
echo "=== Memory Usage ==="
redis-cli info memory | grep -E "used_memory_human|used_memory_peak_human|maxmemory_human|mem_fragmentation_ratio"

echo ""
echo "=== Memory Policy ==="
redis-cli config get maxmemory-policy

echo ""
echo "=== Connected Clients ==="
redis-cli info clients | grep connected_clients

echo ""
echo "=== Keyspace ==="
redis-cli info keyspace

echo ""
echo "=== Evicted Keys (keys removed due to maxmemory) ==="
redis-cli info stats | grep evicted_keys

echo ""
echo "=== Cache Hit Rate ==="
HITS=$(redis-cli info stats | grep keyspace_hits | cut -d: -f2 | tr -d '\r')
MISSES=$(redis-cli info stats | grep keyspace_misses | cut -d: -f2 | tr -d '\r')
if [ "$HITS" != "" ] && [ "$MISSES" != "" ]; then
    TOTAL=$((HITS + MISSES))
    if [ $TOTAL -gt 0 ]; then
        HIT_RATE=$(echo "scale=2; $HITS * 100 / $TOTAL" | bc)
        echo "Hit Rate: ${HIT_RATE}%"
    fi
fi

echo ""
echo "=== Slow Queries (if any) ==="
redis-cli slowlog get 5
