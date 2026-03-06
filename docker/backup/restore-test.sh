#!/usr/bin/env bash
set -euo pipefail

RESTORE_DB="procivo_restore_test"
TIMESTAMP=$(date -u +%Y-%m-%dT%H:%M:%SZ)

echo "[$TIMESTAMP] Starting restore test..."

# Find latest backup
LATEST=$(aws s3 ls "s3://${S3_BUCKET}/backups/" --recursive --endpoint-url "$S3_ENDPOINT" \
    | grep '\.sql\.gz$' \
    | sort \
    | tail -1 \
    | awk '{print $4}')

if [ -z "$LATEST" ]; then
    echo "[$TIMESTAMP] FAIL: No backup files found in S3"
    exit 1
fi

echo "[$TIMESTAMP] Found latest backup: $LATEST"

# Download backup
aws s3 cp "s3://${S3_BUCKET}/${LATEST}" /tmp/restore_test.sql.gz \
    --endpoint-url "$S3_ENDPOINT" --quiet

# Get source table count for comparison
SOURCE_TABLE_COUNT=$(PGPASSWORD="$PGPASSWORD" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -t -A -c \
    "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public';")

echo "[$TIMESTAMP] Source database has $SOURCE_TABLE_COUNT public tables"

# Create temp database
PGPASSWORD="$PGPASSWORD" createdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$RESTORE_DB"

echo "[$TIMESTAMP] Created temp database: $RESTORE_DB"

# Restore backup
gunzip -c /tmp/restore_test.sql.gz | PGPASSWORD="$PGPASSWORD" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$RESTORE_DB" > /dev/null 2>&1

echo "[$TIMESTAMP] Backup restored to $RESTORE_DB"

# Validate schema — count tables
RESTORED_TABLE_COUNT=$(PGPASSWORD="$PGPASSWORD" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$RESTORE_DB" -t -A -c \
    "SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public';")

echo "[$TIMESTAMP] Restored database has $RESTORED_TABLE_COUNT public tables"

# Validate key tables exist
RESULT="PASS"
DETAILS=""

for TABLE in identity_users task_manager_tasks workflow_process_definitions organization_organizations; do
    EXISTS=$(PGPASSWORD="$PGPASSWORD" psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$RESTORE_DB" -t -A -c \
        "SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_name = '$TABLE');")
    if [ "$EXISTS" = "t" ]; then
        DETAILS="${DETAILS}  [OK] $TABLE exists\n"
    else
        DETAILS="${DETAILS}  [MISSING] $TABLE not found\n"
        RESULT="FAIL"
    fi
done

# Compare table counts
if [ "$RESTORED_TABLE_COUNT" -ne "$SOURCE_TABLE_COUNT" ]; then
    DETAILS="${DETAILS}  [MISMATCH] Table count: source=$SOURCE_TABLE_COUNT restored=$RESTORED_TABLE_COUNT\n"
    RESULT="FAIL"
else
    DETAILS="${DETAILS}  [OK] Table count matches: $SOURCE_TABLE_COUNT\n"
fi

# Cleanup: drop temp database
PGPASSWORD="$PGPASSWORD" dropdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$RESTORE_DB"
rm -f /tmp/restore_test.sql.gz

echo "[$TIMESTAMP] Cleaned up temp database and temp file"

# Output result
FINAL_TIMESTAMP=$(date -u +%Y-%m-%dT%H:%M:%SZ)
echo ""
echo "========================================="
echo "  RESTORE TEST: $RESULT"
echo "  Time: $FINAL_TIMESTAMP"
echo "  Backup: $LATEST"
echo "  Tables: source=$SOURCE_TABLE_COUNT restored=$RESTORED_TABLE_COUNT"
echo "-----------------------------------------"
echo -e "$DETAILS"
echo "========================================="

# Upload result as metadata to S3
echo "$RESULT $FINAL_TIMESTAMP backup=$LATEST tables=$RESTORED_TABLE_COUNT/$SOURCE_TABLE_COUNT" \
    | aws s3 cp - "s3://${S3_BUCKET}/backups/restore-test-results/$(date +%Y%m%d).txt" \
    --endpoint-url "$S3_ENDPOINT" --quiet

echo "[$FINAL_TIMESTAMP] Result uploaded to S3"

if [ "$RESULT" = "FAIL" ]; then
    exit 1
fi
