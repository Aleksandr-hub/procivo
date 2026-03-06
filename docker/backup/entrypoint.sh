#!/usr/bin/env bash
set -euo pipefail

echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] Initializing backup service..."

# Create S3 bucket if not exists
aws s3 mb "s3://${S3_BUCKET}" --endpoint-url "$S3_ENDPOINT" 2>/dev/null || true

# Configure S3 lifecycle policy for retention
cat > /tmp/lifecycle.json <<'LIFECYCLE'
{
  "Rules": [
    {
      "ID": "daily-retention",
      "Filter": {"Prefix": "backups/daily/"},
      "Status": "Enabled",
      "Expiration": {"Days": 30}
    },
    {
      "ID": "weekly-retention",
      "Filter": {"Prefix": "backups/weekly/"},
      "Status": "Enabled",
      "Expiration": {"Days": 90}
    },
    {
      "ID": "monthly-retention",
      "Filter": {"Prefix": "backups/monthly/"},
      "Status": "Enabled",
      "Expiration": {"Days": 365}
    }
  ]
}
LIFECYCLE

aws s3api put-bucket-lifecycle-configuration \
    --bucket "$S3_BUCKET" \
    --lifecycle-configuration file:///tmp/lifecycle.json \
    --endpoint-url "$S3_ENDPOINT"

rm -f /tmp/lifecycle.json

echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] S3 lifecycle policy applied"

# Set up daily cron at 02:00 UTC
echo "0 2 * * * /usr/local/bin/backup.sh >> /var/log/backup.log 2>&1" | crontab -

echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] Cron scheduled: daily at 02:00 UTC"

# Run initial backup on startup (useful for dev/testing)
echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] Running initial backup..."
/usr/local/bin/backup.sh

# Start cron in foreground
echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] Starting cron daemon..."
crond -f -l 2
