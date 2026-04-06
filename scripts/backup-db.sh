#!/bin/bash
# BSC-045: Daily database backup with retention and error alerting.
# Usage: bash scripts/backup-db.sh
# Cron: 0 2 * * * /path/to/scripts/backup-db.sh

CONFIG_FILE="$(dirname "$0")/backup-config.sh"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "[$(date)] ERROR: backup-config.sh not found at $CONFIG_FILE" >&2
    exit 1
fi
source "$CONFIG_FILE"

DATE=$(date +%F-%H-%M)
DEST_DIR="$BACKUP_LOCAL/db"
DEST_FILE="$DEST_DIR/db-$DATE.sql.gz"

mkdir -p "$DEST_DIR"

# Run dump
if mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction --quick --lock-tables=false \
    "$DB_NAME" | gzip > "$DEST_FILE"; then
    SIZE=$(du -sh "$DEST_FILE" | cut -f1)
    echo "[$(date)] OK  DB backup: $DEST_FILE ($SIZE)" >> "$LOG_FILE"
else
    echo "[$(date)] FAIL DB backup failed for $DB_NAME" >> "$LOG_FILE"
    if command -v mail &>/dev/null; then
        echo "BSC DB backup FAILED at $(date)" | mail -s "BSC Backup Error" "$ADMIN_EMAIL"
    fi
    exit 1
fi

# Purge old backups
find "$DEST_DIR" -type f -name "*.sql.gz" -mtime +"$RETENTION_DAYS" -delete
echo "[$(date)] INFO Purged DB backups older than ${RETENTION_DAYS} days" >> "$LOG_FILE"
