#!/bin/bash
# BSC-148: Daily VPS state backup (nginx/apache/php/mysql/cron/systemd/ssh inventory + config).
# Usage: bash scripts/backup-vps-state.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=backup-common.sh
source "${SCRIPT_DIR}/backup-common.sh"

load_backup_config

DATE="$(date +%F-%H-%M)"
DEST_DIR="${BACKUP_LOCAL}/system"
DEST_FILE="${DEST_DIR}/system-${DATE}.tar.gz"

ensure_directory "$DEST_DIR"

if backup_vps_state "$DEST_FILE"; then
    write_checksum_file "$DEST_FILE"
    push_remote_artifacts "system" "$DEST_FILE" "${DEST_FILE}.sha256"
    purge_old_files "$DEST_DIR" "*.tar.gz" "$SYSTEM_RETENTION_DAYS"
    purge_old_files "$DEST_DIR" "*.sha256" "$SYSTEM_RETENTION_DAYS"

    BODY="$(cat <<EOF
VPS state backup completed.

Artifact:
$(artifact_summary "$DEST_FILE")

Includes:
- service configuration snapshots
- cron and systemd inventory
- open ports and package inventory
- nginx/apache virtual host discovery
- SSL and SSH config paths
EOF
)"
    log_message "OK" "VPS state backup complete: $DEST_FILE"
    send_email "BSC VPS state backup OK" "$BODY"
else
    log_message "FAIL" "VPS state backup failed"
    send_email "BSC VPS state backup FAILED" "VPS state backup failed at $(date). Check ${LOG_FILE}."
    exit 1
fi
