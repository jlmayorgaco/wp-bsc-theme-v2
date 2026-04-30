#!/bin/bash
# BSC-148: Install backup cron entries for the current user.
# Usage: bash scripts/install-backup-cron.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMP_CRON="$(mktemp)"
CURRENT_CRON="$(mktemp)"

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
    echo "$END_MARKER"
} >> "$TEMP_CRON"

crontab "$TEMP_CRON"
rm -f "$TEMP_CRON" "$CURRENT_CRON"

echo "BSC backup cron installed for $(whoami)."