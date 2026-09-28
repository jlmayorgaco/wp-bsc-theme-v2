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

if [ "$APPLY_DB" = "1" ] || [ "$APPLY_WORDPRESS" = "1" ] || [ "$APPLY_SYSTEM_CONFIG" = "1" ]; then
    load_backup_config
fi
bash "$SCRIPT_DIR/backup-verify.sh" "$BUNDLE_PATH"
ensure_directory "$RESTORE_ROOT"

EXTRACT_DIR="$(mktemp -d "${RESTORE_ROOT%/}/bundle.XXXXXXXX")"
python3 "$SCRIPT_DIR/check-archive-paths.py" "$BUNDLE_PATH"
tar -xzf "$BUNDLE_PATH" -C "$EXTRACT_DIR"

DB_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'db-*.sql.gz' | head -1)"
APP_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'wordpress-*.tar.gz' | head -1)"
SYSTEM_ARCHIVE="$(find "$EXTRACT_DIR" -maxdepth 1 -type f -name 'system-*.tar.gz' | head -1)"

if [ -z "$DB_ARCHIVE" ] || [ -z "$APP_ARCHIVE" ] || [ -z "$SYSTEM_ARCHIVE" ]; then
    echo "Bundle is incomplete. Expected DB, WordPress, and system archives." >&2
    exit 1
fi

for archive in "$DB_ARCHIVE" "$APP_ARCHIVE" "$SYSTEM_ARCHIVE"; do
    bash "$SCRIPT_DIR/backup-verify.sh" "$archive"
done
python3 "$SCRIPT_DIR/check-archive-paths.py" "$APP_ARCHIVE"
python3 "$SCRIPT_DIR/check-archive-paths.py" "$SYSTEM_ARCHIVE"
APP_STAGE_DIR="$(mktemp -d "${RESTORE_ROOT%/}/wordpress-stage.XXXXXXXX")"
tar -xzf "$APP_ARCHIVE" -C "$APP_STAGE_DIR"
SYSTEM_STAGE_DIR="$(mktemp -d "${RESTORE_ROOT%/}/system-stage.XXXXXXXX")"
tar -xzf "$SYSTEM_ARCHIVE" -C "$SYSTEM_STAGE_DIR"

if [ "$APPLY_DB" = "1" ]; then
    require_command mysql
    PRE_RESTORE_DB="$BACKUP_LOCAL/pre-restore/db-$(date +%F-%H-%M-%S).sql.gz"
    backup_database "$PRE_RESTORE_DB"
    write_checksum_file "$PRE_RESTORE_DB"
    gunzip -c "$DB_ARCHIVE" | MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME"
fi

if [ "$APPLY_WORDPRESS" = "1" ]; then
    if [ -z "$WP_RESTORE_ROOT" ]; then
        WP_RESTORE_ROOT="$WP_ROOT"
    fi
    mkdir -p "$WP_RESTORE_ROOT"
    WP_RESTORE_ROOT="$(cd "$WP_RESTORE_ROOT" && pwd -P)"
    if [ "$WP_RESTORE_ROOT" = / ] || [ ! -f "$APP_STAGE_DIR/wp-load.php" ]; then
        echo "Refusing an invalid WordPress destination or incomplete application archive" >&2
        exit 1
    fi
    require_command rsync
    # Extract only into a fresh directory; rsync replaces destination directory links.
    rsync -a --safe-links "$APP_STAGE_DIR/" "$WP_RESTORE_ROOT/"
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
WordPress staged in: $APP_STAGE_DIR
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
