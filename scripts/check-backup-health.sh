#!/bin/bash
# Fail unless both a verified local artifact and a recent remote success exist.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/backup-common.sh"
load_backup_config
failed=0
for spec in 'db:*.sql.gz:2' 'full:*.tar.gz:2' 'system:*.tar.gz:2' 'bundles:*.tar.gz:8'; do
    IFS=: read -r kind pattern days <<< "$spec"
    latest="$(find "$BACKUP_LOCAL/$kind" -maxdepth 1 -type f -name "$pattern" -mtime "-$days" -printf '%T@ %p\n' 2>/dev/null | sort -nr | head -1 | cut -d' ' -f2-)" || latest=""
    if [ -z "$latest" ] || ! bash "$SCRIPT_DIR/backup-verify.sh" "$latest"; then
        log_message FAIL "$kind: no recent verified local backup"
        failed=1
    fi
    if [ "${REMOTE_REQUIRED:-1}" = 1 ]; then
        stamp=0
        if [ -f "$BACKUP_LOCAL/status/$kind.remote-ok" ]; then
            read -r stamp < "$BACKUP_LOCAL/status/$kind.remote-ok" || stamp=0
        fi
        if [[ ! "$stamp" =~ ^[0-9]+$ ]] || (( $(date +%s) - stamp > days * 86400 )); then
            log_message FAIL "$kind: remote copy is missing or stale"
            failed=1
        fi
    fi
done
if [ "$failed" = 1 ]; then
    send_email 'BSC backup health FAILED' 'Local integrity or remote freshness failed. Review the backup log.'
fi
exit "$failed"
