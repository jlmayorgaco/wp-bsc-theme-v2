#!/bin/bash
# BSC-047/BSC-148: Production health check - HTTP, disk, SSL, and backup freshness.
# Usage: bash scripts/monitor-health.sh
# Cron: */15 * * * * /path/to/scripts/monitor-health.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/backup-common.sh"
load_backup_config
SITE_HOST="$(printf '%s' "$SITE_URL" | sed -E 's#https?://([^/]+).*#\1#')"
DISK_THRESHOLD=80    # alert when disk > 80%
SSL_WARN_DAYS=30     # alert when SSL expires in < 30 days
HEALTH_FAILED=0
mkdir -p "$(dirname "$LOG_FILE")"

send_alert() {
    local subject="$1"
    local body="$2"
    HEALTH_FAILED=1
    echo "[$(date)] ALERT: $body" >> "$LOG_FILE"
    if command -v mail &>/dev/null; then
        echo "$body" | mail -s "$subject" "$ADMIN_EMAIL" || echo "Alert email delivery failed" >> "$LOG_FILE"
    fi
}

check_backup_freshness() {
    local label="$1"
    local directory="$2"
    local pattern="$3"
    local max_age_days="$4"

    if [ ! -d "$directory" ]; then
        send_alert "BSC ${label} backup missing" "Backup directory does not exist: $directory"
        return
    fi

    local latest
    latest=$(find "$directory" -type f -name "$pattern" -mtime "-${max_age_days}" | head -1)
    if [ -z "$latest" ]; then
        send_alert "BSC ${label} backup stale" \
            "No ${label} backup matching ${pattern} found in the last ${max_age_days} day(s) in ${directory}."
    else
        echo "[$(date)] OK  Recent ${label} backup found: $latest" >> "$LOG_FILE"
    fi
}

# Check 1: HTTP status
HTTP_CODE=$(curl -sL -o /dev/null -w "%{http_code}" --max-time 10 "$SITE_URL" 2>/dev/null) || HTTP_CODE="connection-failed"
if [ "$HTTP_CODE" != "200" ]; then
    send_alert "BSC Site Down - HTTP $HTTP_CODE" \
        "$SITE_URL returned HTTP $HTTP_CODE at $(date). Check server immediately."
else
    echo "[$(date)] OK  HTTP $HTTP_CODE $SITE_URL" >> "$LOG_FILE"
fi

# Check 2: Disk usage
DISK_USAGE=$(df / | awk 'NR==2{gsub(/%/,"",$5); print $5}') || DISK_USAGE=""
if ! [[ "$DISK_USAGE" =~ ^[0-9]+$ ]]; then
    send_alert "BSC Disk Check Failed" "Could not read filesystem usage."
elif [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    send_alert "BSC Disk Warning - ${DISK_USAGE}% used" \
        "Server disk is at ${DISK_USAGE}%. Free up space before it fills completely."
else
    echo "[$(date)] OK  Disk ${DISK_USAGE}%" >> "$LOG_FILE"
fi

# Check 3: SSL certificate expiry
if command -v openssl &>/dev/null; then
    SSL_EXPIRY=$(echo | timeout 5 openssl s_client -connect "${SITE_HOST}:443" -servername "$SITE_HOST" 2>/dev/null \
        | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2) || SSL_EXPIRY=""

    if [ -n "$SSL_EXPIRY" ]; then
        EXPIRY_EPOCH=$(date -d "$SSL_EXPIRY" +%s 2>/dev/null) || EXPIRY_EPOCH=""
        NOW_EPOCH=$(date +%s)
        if [ -n "$EXPIRY_EPOCH" ]; then
            DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))
            if [ "$DAYS_LEFT" -lt "$SSL_WARN_DAYS" ]; then
                send_alert "BSC SSL Expiring - ${DAYS_LEFT} days left" \
                    "SSL certificate for $SITE_HOST expires in $DAYS_LEFT days ($SSL_EXPIRY). Renew immediately."
            else
                echo "[$(date)] OK  SSL valid for ${DAYS_LEFT} days" >> "$LOG_FILE"
            fi
        else
            send_alert "BSC SSL Check Failed" "Could not parse certificate expiry for $SITE_HOST."
        fi
    else
        send_alert "BSC SSL Check Failed" "Could not retrieve certificate for $SITE_HOST."
    fi
else
    send_alert "BSC SSL Check Failed" "openssl is unavailable."
fi

# Check 4: Backup freshness
bash "$SCRIPT_DIR/check-backup-health.sh" || HEALTH_FAILED=1

echo "[$(date)] --- Health check complete ---" >> "$LOG_FILE"
exit "$HEALTH_FAILED"
