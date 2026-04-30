#!/bin/bash
# BSC-148: Create a single recovery bundle with DB, WordPress app, VPS snapshot, and manifest.
# Usage: bash scripts/backup-recovery-bundle.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=backup-common.sh
source "${SCRIPT_DIR}/backup-common.sh"

load_backup_config

DATE="$(date +%F-%H-%M)"
DEST_DIR="${BACKUP_LOCAL}/bundles"
DEST_FILE="${DEST_DIR}/recovery-bundle-${DATE}.tar.gz"

ensure_directory "$DEST_DIR"

STAGING_DIR="$(mktemp -d)"
DB_FILE="${STAGING_DIR}/db-${DATE}.sql.gz"
APP_FILE="${STAGING_DIR}/wordpress-${DATE}.tar.gz"
SYSTEM_FILE="${STAGING_DIR}/system-${DATE}.tar.gz"
MANIFEST_FILE="${STAGING_DIR}/bundle-manifest.txt"

backup_database "$DB_FILE"
backup_wordpress_application "$APP_FILE"
backup_vps_state "$SYSTEM_FILE"

write_checksum_file "$DB_FILE"
write_checksum_file "$APP_FILE"
write_checksum_file "$SYSTEM_FILE"

{
    echo "site_name=${SITE_NAME}"
    echo "site_url=${SITE_URL}"
    echo "bundle_created_at=$(date '+%F %T %Z')"
    echo "hostname=$(hostname 2>/dev/null || true)"
    echo "wp_root=${WP_ROOT}"
    echo "db_name=${DB_NAME}"
    echo
    echo "[artifacts]"
    artifact_summary "$DB_FILE"
    artifact_summary "$APP_FILE"
    artifact_summary "$SYSTEM_FILE"
    echo
    echo "[restore_order]"
    echo "1. Extract bundle."
    echo "2. Restore database."
    echo "3. Restore WordPress application archive."
    echo "4. Stage or apply VPS system snapshot."
    echo "5. Validate services, SSL, ports, cron, and application flows."
} > "$MANIFEST_FILE"

tar -czf "$DEST_FILE" -C "$STAGING_DIR" .
write_checksum_file "$DEST_FILE"
push_remote_artifacts "bundles" "$DEST_FILE" "${DEST_FILE}.sha256"
purge_old_files "$DEST_DIR" "*.tar.gz" "$BUNDLE_RETENTION_DAYS"
purge_old_files "$DEST_DIR" "*.sha256" "$BUNDLE_RETENTION_DAYS"

BODY="$(cat <<EOF
Recovery bundle completed.

Artifact:
$(artifact_summary "$DEST_FILE")

This bundle includes:
- database dump
- WordPress application archive
- VPS state snapshot
- manifest and checksums

Recommended action:
- keep the artifact off-site
- do not rely on email attachment as the primary recovery copy
- run a restore drill in staging
EOF
)"

log_message "OK" "Recovery bundle complete: $DEST_FILE"
send_email "BSC recovery bundle OK" "$BODY"
rm -rf "$STAGING_DIR"
