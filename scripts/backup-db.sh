#!/bin/bash
# BSC-148: Daily database backup with retention, remote copy, checksum, and email report.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=backup-common.sh
source "${SCRIPT_DIR}/backup-common.sh"

load_backup_config

DATE="$(date +%F-%H-%M)"
DEST_DIR="${BACKUP_LOCAL}/db"
DEST_FILE="${DEST_DIR}/db-${DATE}.sql.gz"

ensure_directory "$DEST_DIR"

if backup_database "$DEST_FILE"; then
    write_checksum_file "$DEST_FILE"
    push_remote_artifacts "db" "$DEST_FILE" "${DEST_FILE}.sha256"
    purge_old_files "$DEST_DIR" "*.sql.gz" "$DB_RETENTION_DAYS"
    purge_old_files "$DEST_DIR" "*.sql.gz.sha256" "$DB_RETENTION_DAYS"

    BODY="$(cat <<EOF
Database backup completed.

Artifact:
$(artifact_summary "$DEST_FILE")

Database:
- host: $DB_HOST
- name: $DB_NAME
EOF
)"
    log_message "OK" "DB backup complete: $DEST_FILE"
    send_email "BSC DB backup OK" "$BODY"
else
    log_message "FAIL" "DB backup failed for $DB_NAME"
    send_email "BSC DB backup FAILED" "Database backup failed at $(date). Check ${LOG_FILE}."
    exit 1
fi
