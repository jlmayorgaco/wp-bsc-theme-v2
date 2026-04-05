#!/bin/bash
# BSC-045: Weekly full backup (uploads + theme + DB) with remote copy.
# Usage: bash scripts/backup-full.sh
# Cron: 0 3 * * 0 /path/to/scripts/backup-full.sh

CONFIG_FILE="$(dirname "$0")/backup-config.sh"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "[$(date)] ERROR: backup-config.sh not found at $CONFIG_FILE" >&2
    exit 1
fi
source "$CONFIG_FILE"

DATE=$(date +%F)
DEST_DIR="$BACKUP_LOCAL/full"
DEST_FILE="$DEST_DIR/full-$DATE.tar.gz"

mkdir -p "$DEST_DIR"

# 1. Archive uploads + theme (excluding node_modules and .git)
if tar -czf "$DEST_FILE" \
    --exclude="*/node_modules" \
    --exclude="*/.git" \
    -C "$WP_ROOT" \
    "wp-content/uploads" \
    "wp-content/themes/wp-bsc-theme-v2"; then
    echo "[$(date)] OK  Files archived: $DEST_FILE" >> "$LOG_FILE"
else
    echo "[$(date)] FAIL File archive failed" >> "$LOG_FILE"
    exit 1
fi

# 2. Append DB dump to the same archive
if mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction --quick --lock-tables=false \
    "$DB_NAME" | gzip > "${DEST_FILE%.tar.gz}-db.sql.gz"; then
    echo "[$(date)] OK  DB appended for full backup" >> "$LOG_FILE"
else
    echo "[$(date)] FAIL DB dump failed during full backup" >> "$LOG_FILE"
fi

# 3. Copy to remote destination
if command -v rsync &>/dev/null && [ -n "$REMOTE_DEST" ]; then
    if rsync -az "$DEST_FILE" "${DEST_FILE%.tar.gz}-db.sql.gz" "$REMOTE_DEST/full/" 2>> "$LOG_FILE"; then
        echo "[$(date)] OK  Remote copy sent to $REMOTE_DEST" >> "$LOG_FILE"
    else
        echo "[$(date)] WARN Remote copy failed — local backup retained" >> "$LOG_FILE"
    fi
fi

SIZE=$(du -sh "$DEST_FILE" | cut -f1)
echo "[$(date)] OK  Full backup complete: $DEST_FILE ($SIZE)" >> "$LOG_FILE"

# 4. Purge old full backups
find "$DEST_DIR" -type f -mtime +"$RETENTION_DAYS" -delete
echo "[$(date)] INFO Purged full backups older than ${RETENTION_DAYS} days" >> "$LOG_FILE"
