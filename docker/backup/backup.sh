#!/usr/bin/env bash
set -euo pipefail

# Required environment variables
: "${PGHOST:?PGHOST is required}"
: "${PGPORT:?PGPORT is required}"
: "${PGUSER:?PGUSER is required}"
: "${PGPASSWORD:?PGPASSWORD is required}"
: "${PGDATABASE:?PGDATABASE is required}"
: "${S3_BUCKET:?S3_BUCKET is required}"
: "${S3_ENDPOINT:?S3_ENDPOINT is required}"
: "${AWS_ACCESS_KEY_ID:?AWS_ACCESS_KEY_ID is required}"
: "${AWS_SECRET_ACCESS_KEY:?AWS_SECRET_ACCESS_KEY is required}"
: "${AWS_DEFAULT_REGION:?AWS_DEFAULT_REGION is required}"

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="procivo_${TIMESTAMP}.sql.gz"

# Determine prefix based on day
DAY_OF_MONTH=$(date +%d)
DAY_OF_WEEK=$(date +%w)

if [ "$DAY_OF_MONTH" -eq 1 ]; then
    PREFIX="monthly/"
elif [ "$DAY_OF_WEEK" -eq 0 ]; then
    PREFIX="weekly/"
else
    PREFIX="daily/"
fi

echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] Starting backup: ${PREFIX}${FILENAME}"

# Create dump and compress
if ! PGPASSWORD="$PGPASSWORD" pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$PGDATABASE" | gzip > "/tmp/${FILENAME}"; then
    echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] ERROR: pg_dump failed"
    rm -f "/tmp/${FILENAME}"
    exit 1
fi

FILE_SIZE=$(du -h "/tmp/${FILENAME}" | cut -f1)

# Upload to S3
if ! aws s3 cp "/tmp/${FILENAME}" "s3://${S3_BUCKET}/backups/${PREFIX}${FILENAME}" \
    --endpoint-url "$S3_ENDPOINT" --quiet; then
    echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] ERROR: S3 upload failed"
    rm -f "/tmp/${FILENAME}"
    exit 1
fi

# Cleanup temp file
rm -f "/tmp/${FILENAME}"

echo "[$(date -u +%Y-%m-%dT%H:%M:%SZ)] SUCCESS: Backup uploaded to s3://${S3_BUCKET}/backups/${PREFIX}${FILENAME} (${FILE_SIZE})"
