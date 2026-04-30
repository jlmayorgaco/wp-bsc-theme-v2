#!/bin/bash
# BSC-148: Stage or apply a recovery bundle.
# Usage:
#   bash scripts/restore-bundle.sh /path/to/recovery-bundle.tar.gz --restore-root /tmp/bsc-restore
#   bash scripts/restore-bundle.sh /path/to/recovery-bundle.tar.gz --restore-root /tmp/bsc-restore --apply-wordpress --wp-root /var/www/html
#   bash scripts/restore-bundle.sh /path/to/recovery-bundle.tar.gz --restore-root /tmp/bsc-restore --apply-db

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=backup-common.sh
source "${SCRIPT_DIR}/backup-common.sh"

if [ "$#" -lt 1 ]; then
    echo "Usage: bash scripts/restore-bundle.sh /path/to/recovery-bundle.tar.gz [--restore-root /tmp/bsc-restore] [--apply-wordpress] [--wp-root /var/www/html] [--apply-db] [--apply-system-config]" >&2
    exit 1
fi

BUNDLE_PATH="$1"
shift

RESTORE_ROOT="/tmp/bsc-restore"
APPLY_WORDPRESS=0
APPLY_DB=0
APPLY_SYSTEM_CONFIG=0
WP_RESTORE_ROOT=""

while [ "$#" -gt 0 ]; do
    case "$1" in
        --restore-root)
            RESTORE_ROOT="$2"
            shift 2
            ;;
        --apply-wordpress)
            APPLY_WORDPRESS=1
            shift
            ;;
        --apply-db)
            APPLY_DB=1
            shift
            ;;
        --apply-system-config)
            APPLY_SYSTEM_CONFIG=1
            shift
            ;;
        --wp-root)
            WP_RESTORE_ROOT="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

load_backup_config
ensure_directory "$RESTORE_ROOT"

EXTRACT_DIR="${RESTORE_ROOT}/bundle"
rm -rf "$EXTRACT_DIR"
mkdir -p "$EXTRACT_DIR"
tar -xzf "$BUNDLE_PATH" -C "$EXTRACT_DIR"

DB_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'db-*.sql.gz' | head -1)"
APP_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'wordpress-*.tar.gz' | head -1)"
SYSTEM_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'system-*.tar.gz' | head -1)"

if [ -z "$DB_ARCHIVE" ] || [ -z "$APP_ARCHIVE" ] || [ -z "$SYSTEM_ARCHIVE" ]; then
    echo "Bundle is incomplete. Expected DB, WordPress, and system archives." >&2
    exit 1
fi

SYSTEM_STAGE_DIR="${RESTORE_ROOT}/system-stage"
rm -rf "$SYSTEM_STAGE_DIR"
mkdir -p "$SYSTEM_STAGE_DIR"
tar -xzf "$SYSTEM_ARCHIVE" -C "$SYSTEM_STAGE_DIR"

if [ "$APPLY_DB" = "1" ]; then
    require_command mysql
    gunzip -c "$DB_ARCHIVE" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
fi

if [ "$APPLY_WORDPRESS" = "1" ]; then
    if [ -z "$WP_RESTORE_ROOT" ]; then
        WP_RESTORE_ROOT="$WP_ROOT"
    fi
    mkdir -p "$WP_RESTORE_ROOT"
    tar -xzf "$APP_ARCHIVE" -C "$WP_RESTORE_ROOT"
fi

if [ "$APPLY_SYSTEM_CONFIG" = "1" ]; then
    if ! command -v rsync >/dev/null 2>&1; then
        echo "rsync is required to apply system config" >&2
        exit 1
    fi
    rsync -a "${SYSTEM_STAGE_DIR}/rootfs/" /
fi

cat <<EOF
Bundle extracted to: $EXTRACT_DIR
System config staged in: $SYSTEM_STAGE_DIR

Flags applied:
- apply_db=$APPLY_DB
- apply_wordpress=$APPLY_WORDPRESS
- apply_system_config=$APPLY_SYSTEM_CONFIG

Next steps:
1. Review bundle-manifest.txt in $EXTRACT_DIR
2. Review inventory files under $SYSTEM_STAGE_DIR/inventory
3. Validate nginx/apache, SSL, cron, systemd, and application flows
EOF