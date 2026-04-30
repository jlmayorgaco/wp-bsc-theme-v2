#!/bin/bash
# BSC-148: WordPress application backup (wp-config + themes + plugins + uploads) plus DB dump.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=backup-common.sh
source "${SCRIPT_DIR}/backup-common.sh"

load_backup_config

DATE="$(date +%F-%H-%M)"
DEST_DIR="${BACKUP_LOCAL}/full"
APP_FILE="${DEST_DIR}/full-${DATE}.tar.gz"
DB_FILE="${DEST_DIR}/full-${DATE}-db.sql.gz"
MANIFEST_FILE="${DEST_DIR}/full-${DATE}.manifest.txt"

ensure_directory "$DEST_DIR"

backup_wordpress_application "$APP_FILE"
backup_database "$DB_FILE"

write_checksum_file "$APP_FILE"
write_checksum_file "$DB_FILE"

{
    echo "site_name=${SITE_NAME}"
    echo "site_url=${SITE_URL}"
    echo "created_at=$(date '+%F %T %Z')"
    echo "wp_root=${WP_ROOT}"
    echo "db_name=${DB_NAME}"
    echo
    echo "[artifacts]"
    artifact_summary "$APP_FILE"
    artifact_summary "$DB_FILE"
} > "$MANIFEST_FILE"

push_remote_artifacts "full" "$APP_FILE" "${APP_FILE}.sha256" "$DB_FILE" "${DB_FILE}.sha256" "$MANIFEST_FILE"
purge_old_files "$DEST_DIR" "*.tar.gz" "$FULL_RETENTION_DAYS"
purge_old_files "$DEST_DIR" "*.sql.gz" "$FULL_RETENTION_DAYS"
purge_old_files "$DEST_DIR" "*.sha256" "$FULL_RETENTION_DAYS"
purge_old_files "$DEST_DIR" "*.manifest.txt" "$FULL_RETENTION_DAYS"

BODY="$(cat <<EOF
WordPress application backup completed.

Application artifact:
$(artifact_summary "$APP_FILE")

Database artifact:
$(artifact_summary "$DB_FILE")

Includes:
- wp-config.php
- themes
- plugins
- mu-plugins
- uploads
- languages
EOF
)"

log_message "OK" "WordPress application backup complete: $APP_FILE"
send_email "BSC WordPress backup OK" "$BODY"
