#!/bin/bash
# BSC-148: Install backup cron entries for the current user.
# Usage: bash scripts/install-backup-cron.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/backup-common.sh"
load_backup_config
TEMP_CRON="$(mktemp)"
CURRENT_CRON="$(mktemp)"
WP_CLI="${BSC_WP_CLI:-$(command -v wp || true)}"
WP_CRON_USER="${WP_CRON_USER:-www-data}"
trap 'rm -f -- "$TEMP_CRON" "$CURRENT_CRON"' EXIT
if [ -z "$WP_CLI" ]; then
    echo "WP-CLI required: WordPress reservations, retries and retention depend on cron." >&2
    exit 1
fi
WP_RUNNER=""
if [ "$(id -u)" = "0" ]; then
    id "$WP_CRON_USER" >/dev/null
    WP_RUNNER="runuser -u ${WP_CRON_USER} -- "
fi

BEGIN_MARKER="# BEGIN BSC BACKUPS"
END_MARKER="# END BSC BACKUPS"

crontab -l > "$CURRENT_CRON" 2>/dev/null || true

awk -v begin="$BEGIN_MARKER" -v end="$END_MARKER" '
    $0 == begin { skip=1; next }
    $0 == end { skip=0; next }
    skip != 1 { print }
' "$CURRENT_CRON" > "$TEMP_CRON"

{
    echo "$BEGIN_MARKER"
    echo "0 2 * * * bash ${SCRIPT_DIR}/backup-db.sh"
    echo "20 2 * * * bash ${SCRIPT_DIR}/backup-vps-state.sh"
    echo "40 2 * * * bash ${SCRIPT_DIR}/backup-full.sh"
    echo "15 3 * * 0 bash ${SCRIPT_DIR}/backup-recovery-bundle.sh"
    echo "*/15 * * * * bash ${SCRIPT_DIR}/monitor-health.sh"
    if [ -n "$WP_CLI" ]; then
        echo "*/5 * * * * ${WP_RUNNER}'${WP_CLI}' cron event run --due-now --quiet --path='${WP_ROOT}' >> '${BACKUP_LOCAL}/wp-cron.log' 2>&1"
    else
        echo "# WP-CLI not found during installation; configure BSC_WP_CLI and rerun this script to execute WordPress cron."
    fi
    echo "$END_MARKER"
} >> "$TEMP_CRON"

crontab "$TEMP_CRON"
rm -f "$TEMP_CRON" "$CURRENT_CRON"

echo "BSC backup cron installed for $(whoami)."
