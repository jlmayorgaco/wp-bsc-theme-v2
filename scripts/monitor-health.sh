#!/bin/bash
# BSC-047: Production health check — HTTP, disk, SSL.
# Usage: bash scripts/monitor-health.sh
# Cron: */15 * * * * /path/to/scripts/monitor-health.sh

SITE_URL="https://bubbleskincare.co"
SITE_HOST="bubbleskincare.co"
ADMIN_EMAIL="admin@bubbleskincare.co"
LOG_FILE="/var/log/bsc-monitor.log"
DISK_THRESHOLD=80    # alert when disk > 80%
SSL_WARN_DAYS=30     # alert when SSL expires in < 30 days

send_alert() {
    local subject="$1"
    local body="$2"
    echo "[$(date)] ALERT: $body" >> "$LOG_FILE"
    if command -v mail &>/dev/null; then
        echo "$body" | mail -s "$subject" "$ADMIN_EMAIL"
    fi
}

# ── Check 1: HTTP status ───────────────────────────────────────────────────
HTTP_CODE=$(curl -sL -o /dev/null -w "%{http_code}" --max-time 10 "$SITE_URL" 2>/dev/null)
if [ "$HTTP_CODE" != "200" ]; then
    send_alert "BSC Site Down — HTTP $HTTP_CODE" \
        "bubbleskincare.co returned HTTP $HTTP_CODE at $(date). Check server immediately."
else
    echo "[$(date)] OK  HTTP $HTTP_CODE $SITE_URL" >> "$LOG_FILE"
fi

# ── Check 2: Disk usage ────────────────────────────────────────────────────
DISK_USAGE=$(df / | awk 'NR==2{gsub(/%/,"",$5); print $5}')
if [ -n "$DISK_USAGE" ] && [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    send_alert "BSC Disk Warning — ${DISK_USAGE}% used" \
        "Server disk is at ${DISK_USAGE}%. Free up space before it fills completely."
else
    echo "[$(date)] OK  Disk ${DISK_USAGE}%" >> "$LOG_FILE"
fi

# ── Check 3: SSL certificate expiry ───────────────────────────────────────
if command -v openssl &>/dev/null; then
    SSL_EXPIRY=$(echo | timeout 5 openssl s_client -connect "${SITE_HOST}:443" -servername "$SITE_HOST" 2>/dev/null \
        | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)

    if [ -n "$SSL_EXPIRY" ]; then
        EXPIRY_EPOCH=$(date -d "$SSL_EXPIRY" +%s 2>/dev/null)
        NOW_EPOCH=$(date +%s)
        if [ -n "$EXPIRY_EPOCH" ]; then
            DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))
            if [ "$DAYS_LEFT" -lt "$SSL_WARN_DAYS" ]; then
                send_alert "BSC SSL Expiring — ${DAYS_LEFT} days left" \
                    "SSL certificate for $SITE_HOST expires in $DAYS_LEFT days ($SSL_EXPIRY). Renew immediately."
            else
                echo "[$(date)] OK  SSL valid for ${DAYS_LEFT} days" >> "$LOG_FILE"
            fi
        fi
    fi
fi

# ── Check 4: Backup freshness (DB backup should exist from today or yesterday) ──
BACKUP_DIR="/backups/bsc/db"
if [ -d "$BACKUP_DIR" ]; then
    LATEST=$(find "$BACKUP_DIR" -name "*.sql.gz" -mtime -2 | head -1)
    if [ -z "$LATEST" ]; then
        send_alert "BSC Backup Missing" \
            "No DB backup found in the last 48 hours in $BACKUP_DIR. Check the backup cron job."
    else
        echo "[$(date)] OK  Recent backup found: $LATEST" >> "$LOG_FILE"
    fi
fi

echo "[$(date)] --- Health check complete ---" >> "$LOG_FILE"
