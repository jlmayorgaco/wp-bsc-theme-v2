#!/bin/bash
# BSC-148: shared helpers for DB, app, VPS-state, and recovery-bundle backups.

set -euo pipefail
umask 077

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${BSC_BACKUP_CONFIG:-$SCRIPT_DIR/backup-config.sh}"

require_command() {
    local command_name="$1"
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Missing required command: $command_name" >&2
        exit 1
    fi
}

load_backup_config() {
    if [ ! -f "$CONFIG_FILE" ]; then
        echo "Backup config not found: $CONFIG_FILE" >&2
        exit 1
    fi

    # shellcheck disable=SC1090
    source "$CONFIG_FILE"

    : "${DB_HOST:?DB_HOST is required}"
    : "${DB_NAME:?DB_NAME is required}"
    : "${DB_USER:?DB_USER is required}"
    : "${DB_PASS:?DB_PASS is required}"
    : "${WP_ROOT:?WP_ROOT is required}"
    : "${BACKUP_LOCAL:?BACKUP_LOCAL is required}"
    : "${LOG_FILE:?LOG_FILE is required}"

    SITE_NAME="${SITE_NAME:-Bubble Skin Care}"
    SITE_URL="${SITE_URL:-https://bubblesskincare.com}"
    RETENTION_DAYS="${RETENTION_DAYS:-30}"
    DB_RETENTION_DAYS="${DB_RETENTION_DAYS:-$RETENTION_DAYS}"
    FULL_RETENTION_DAYS="${FULL_RETENTION_DAYS:-$RETENTION_DAYS}"
    SYSTEM_RETENTION_DAYS="${SYSTEM_RETENTION_DAYS:-$RETENTION_DAYS}"
    BUNDLE_RETENTION_DAYS="${BUNDLE_RETENTION_DAYS:-$RETENTION_DAYS}"
    ADMIN_EMAIL="${ADMIN_EMAIL:-}"
    REMOTE_DEST="${REMOTE_DEST:-}"
    REMOTE_REQUIRED="${REMOTE_REQUIRED:-1}"
    REMOTE_REQUIRE_ENCRYPTION="${REMOTE_REQUIRE_ENCRYPTION:-1}"
    AGE_RECIPIENT="${AGE_RECIPIENT:-}"
    EMAIL_ATTACH_BACKUP="${EMAIL_ATTACH_BACKUP:-0}"
    EMAIL_ATTACH_MAX_MB="${EMAIL_ATTACH_MAX_MB:-10}"
    SYSTEM_BACKUP_ROOTS="${SYSTEM_BACKUP_ROOTS:-/etc/nginx /etc/apache2 /etc/httpd /etc/letsencrypt /etc/cron.d /var/spool/cron /var/spool/cron/crontabs /etc/systemd/system /etc/ssh /etc/mysql /etc/php}"
    SYSTEM_BACKUP_FILES="${SYSTEM_BACKUP_FILES:-/etc/crontab /etc/fstab /etc/hosts /etc/environment /etc/passwd /etc/group}"
    WORDPRESS_BACKUP_PATHS="${WORDPRESS_BACKUP_PATHS:-.}"
    WORDPRESS_EXCLUDES="${WORDPRESS_EXCLUDES:-wp-content/cache wp-content/upgrade wp-content/ai1wm-backups wp-content/backups node_modules .git}"
    EXTRA_BACKUP_PATHS="${EXTRA_BACKUP_PATHS:-$(dirname "$WP_ROOT")/bsc-private/skin-quiz}"
    export WP_ROOT BACKUP_LOCAL
    set -E
    trap 'backup_failure "$?" "$LINENO"' ERR
}

backup_failure() {
    local result="$1" line="$2"
    trap - ERR
    log_message "FAIL" "Backup/restore failed at line $line (exit $result)" || true
    send_email "BSC backup FAILED" "Operation failed at line $line. Review ${LOG_FILE}." || true
    return "$result"
}

log_message() {
    local level="$1"
    local message="$2"
    mkdir -p "$(dirname "$LOG_FILE")"
    printf '[%s] %s %s\n' "$(date '+%F %T')" "$level" "$message" >> "$LOG_FILE"
}

send_email() {
    local subject="$1"
    local body="$2"
    local attachment_path="${3:-}"

    if [ -z "$ADMIN_EMAIL" ]; then
        return 0
    fi

    if ! command -v mail >/dev/null 2>&1; then
        log_message "WARN" "mail command not found; email not sent: $subject"
        return 0
    fi

    if [ -n "$attachment_path" ] && [ "$EMAIL_ATTACH_BACKUP" = "1" ] && [ -f "$attachment_path" ]; then
        local attachment_mb
        attachment_mb="$(du -m "$attachment_path" | awk '{print $1}')"
        if [ "${attachment_mb:-0}" -le "$EMAIL_ATTACH_MAX_MB" ] && command -v uuencode >/dev/null 2>&1; then
            {
                printf '%s\n' "$body"
                uuencode "$attachment_path" "$(basename "$attachment_path")"
            } | mail -s "$subject" "$ADMIN_EMAIL"
            return 0
        fi
    fi

    printf '%s\n' "$body" | mail -s "$subject" "$ADMIN_EMAIL"
}

ensure_directory() {
    mkdir -p "$1"
}

checksum_command() {
    if command -v sha256sum >/dev/null 2>&1; then
        echo "sha256sum"
    elif command -v shasum >/dev/null 2>&1; then
        echo "shasum -a 256"
    else
        echo ""
    fi
}

write_checksum_file() {
    local artifact="$1"
    local checksum_tool
    checksum_tool="$(checksum_command)"
    if [ -z "$checksum_tool" ]; then
        log_message "FAIL" "No SHA256 tool found for $artifact"
        return 1
    fi

    if [ "$checksum_tool" = "sha256sum" ]; then
        (cd "$(dirname "$artifact")" && sha256sum -- "$(basename "$artifact")") > "${artifact}.sha256"
    else
        (cd "$(dirname "$artifact")" && shasum -a 256 -- "$(basename "$artifact")") > "${artifact}.sha256"
    fi
}

push_remote_artifacts() {
    local remote_subdir="$1"
    shift
    if [ -z "$REMOTE_DEST" ]; then
        if [ "${REMOTE_REQUIRED:-1}" = "1" ]; then
            log_message "FAIL" "Remote backup is required but REMOTE_DEST is empty"
            return 1
        fi
        log_message "WARN" "Local-only backup explicitly configured"
        return 0
    fi

    if ! command -v rsync >/dev/null 2>&1; then
        log_message "FAIL" "rsync not available; remote copy failed"
        return 1
    fi

    local remote_files=("$@")
    if [ "${REMOTE_REQUIRE_ENCRYPTION:-1}" = "1" ]; then
        if [ -z "${AGE_RECIPIENT:-}" ] || ! command -v age >/dev/null 2>&1; then
            log_message "FAIL" "Encrypted off-site backup requires age and AGE_RECIPIENT; local copy retained"
            return 1
        fi
        local outgoing="$BACKUP_LOCAL/outgoing" encrypted
        ensure_directory "$outgoing"
        encrypted="$outgoing/${remote_subdir}-$(date +%F-%H-%M-%S)-$$.tar.age"
        local tar_items=() item
        for item in "$@"; do
            tar_items+=(-C "$(dirname "$item")" "$(basename "$item")")
        done
        if ! tar -cf - "${tar_items[@]}" | age -r "$AGE_RECIPIENT" -o "${encrypted}.partial"; then
            return 1
        fi
        mv -- "${encrypted}.partial" "$encrypted"
        write_checksum_file "$encrypted"
        remote_files=("$encrypted" "${encrypted}.sha256")
    fi
    if rsync -az --checksum -- "${remote_files[@]}" "${REMOTE_DEST}/${remote_subdir}/"; then
        log_message "OK" "Remote copy sent to ${REMOTE_DEST}/${remote_subdir}/"
        if [ -n "${BACKUP_LOCAL:-}" ]; then
            ensure_directory "$BACKUP_LOCAL/status"
            date +%s > "$BACKUP_LOCAL/status/${remote_subdir}.remote-ok.partial"
            mv -- "$BACKUP_LOCAL/status/${remote_subdir}.remote-ok.partial" "$BACKUP_LOCAL/status/${remote_subdir}.remote-ok"
        fi
        if [ -n "${encrypted:-}" ]; then
            rm -f -- "$encrypted" "${encrypted}.sha256"
        fi
    else
        log_message "FAIL" "Remote copy failed for ${remote_subdir}; local copy retained"
        return 1
    fi
}

purge_old_files() {
    local directory="$1"
    local pattern="$2"
    local retention_days="$3"
    if [ -d "$directory" ]; then
        find "$directory" -type f -name "$pattern" -mtime +"$retention_days" -delete
    fi
}

copy_path_preserving_tree() {
    local source_path="$1"
    local destination_root="$2"

    if [ ! -e "$source_path" ]; then
        return 0
    fi

    if command -v rsync >/dev/null 2>&1; then
        rsync -aR "$source_path" "$destination_root/"
        return $?
    fi

    local parent_dir
    parent_dir="$(dirname "$source_path")"
    mkdir -p "${destination_root}${parent_dir}" || return 1
    cp -a "$source_path" "${destination_root}${parent_dir}/"
}

backup_database() {
    local output_file="$1"
    require_command mysqldump
    ensure_directory "$(dirname "$output_file")"
    if ! MYSQL_PWD="$DB_PASS" mysqldump -h "$DB_HOST" -u "$DB_USER" \
        --single-transaction --quick --lock-tables=false \
        "$DB_NAME" | gzip > "${output_file}.partial"; then
        return 1
    fi
    gzip -t "${output_file}.partial" || return 1
    mv -- "${output_file}.partial" "$output_file"
}

backup_wordpress_application() {
    local output_file="$1"
    ensure_directory "$(dirname "$output_file")"

    local includes=()
    local relative_path
    for relative_path in $WORDPRESS_BACKUP_PATHS; do
        if [ -e "$WP_ROOT/$relative_path" ]; then
            includes+=("$relative_path")
        fi
    done

    if [ "${#includes[@]}" -eq 0 ]; then
        echo "No WordPress paths found under $WP_ROOT" >&2
        exit 1
    fi

    local tar_args=()
    local excluded_path
    for excluded_path in $WORDPRESS_EXCLUDES; do
        tar_args+=("--exclude=$excluded_path")
    done

    tar -czf "${output_file}.partial" "${tar_args[@]}" -C "$WP_ROOT" "${includes[@]}" || return 1
    tar -tzf "${output_file}.partial" >/dev/null || return 1
    mv -- "${output_file}.partial" "$output_file"
}

write_system_inventory() {
    local inventory_dir="$1"
    ensure_directory "$inventory_dir"

    {
        echo "site_name=$SITE_NAME"
        echo "site_url=$SITE_URL"
        echo "hostname=$(hostname 2>/dev/null || true)"
        echo "fqdn=$(hostname -f 2>/dev/null || true)"
        echo "timestamp=$(date '+%F %T %Z')"
        echo "wp_root=$WP_ROOT"
        echo "db_name=$DB_NAME"
    } > "${inventory_dir}/manifest.env"

    {
        uname -a 2>/dev/null || true
        echo
        hostnamectl 2>/dev/null || true
    } > "${inventory_dir}/system-info.txt"

    df -h > "${inventory_dir}/disk-usage.txt" 2>/dev/null || true
    free -m > "${inventory_dir}/memory.txt" 2>/dev/null || true
    ss -tulpn > "${inventory_dir}/open-ports.txt" 2>/dev/null || true
    php -v > "${inventory_dir}/php-version.txt" 2>/dev/null || true
    php -m > "${inventory_dir}/php-modules.txt" 2>/dev/null || true
    mysql --version > "${inventory_dir}/mysql-version.txt" 2>/dev/null || true
    nginx -v > "${inventory_dir}/nginx-version.txt" 2>&1 || true
    apache2ctl -v > "${inventory_dir}/apache-version.txt" 2>/dev/null || httpd -v > "${inventory_dir}/apache-version.txt" 2>/dev/null || true
    wp --info --path="$WP_ROOT" > "${inventory_dir}/wp-cli-info.txt" 2>/dev/null || true
    systemctl list-unit-files > "${inventory_dir}/systemd-unit-files.txt" 2>/dev/null || true
    systemctl list-units --type=service --state=running > "${inventory_dir}/systemd-running-services.txt" 2>/dev/null || true
    systemctl list-timers --all > "${inventory_dir}/systemd-timers.txt" 2>/dev/null || true
    crontab -l > "${inventory_dir}/current-user-crontab.txt" 2>/dev/null || true

    if [ -d /etc/nginx ]; then
        grep -R -n -E 'server_name|listen|root ' /etc/nginx > "${inventory_dir}/nginx-vhosts.txt" 2>/dev/null || true
    fi

    if [ -d /etc/apache2 ]; then
        grep -R -n -E 'ServerName|ServerAlias|DocumentRoot|Listen' /etc/apache2 > "${inventory_dir}/apache-vhosts.txt" 2>/dev/null || true
    fi

    if [ -d /etc/httpd ]; then
        grep -R -n -E 'ServerName|ServerAlias|DocumentRoot|Listen' /etc/httpd > "${inventory_dir}/httpd-vhosts.txt" 2>/dev/null || true
    fi

    if [ -d /etc/letsencrypt/live ]; then
        find /etc/letsencrypt/live -maxdepth 2 -type f > "${inventory_dir}/letsencrypt-files.txt" 2>/dev/null || true
    fi

    if command -v dpkg-query >/dev/null 2>&1; then
        dpkg-query -W -f='${Package} ${Version}\n' > "${inventory_dir}/packages.txt" 2>/dev/null || true
    elif command -v rpm >/dev/null 2>&1; then
        rpm -qa > "${inventory_dir}/packages.txt" 2>/dev/null || true
    fi
}

backup_vps_state() {
    local output_file="$1"
    ensure_directory "$(dirname "$output_file")" || return 1

    local staging_dir
    staging_dir="$(mktemp -d)" || return 1
    local rootfs_dir="${staging_dir}/rootfs"
    local inventory_dir="${staging_dir}/inventory"
    ensure_directory "$rootfs_dir" || return 1
    ensure_directory "$inventory_dir" || return 1

    write_system_inventory "$inventory_dir" || return 1

    local system_path
    for system_path in $SYSTEM_BACKUP_ROOTS; do
        copy_path_preserving_tree "$system_path" "$rootfs_dir" || return 1
    done

    for system_path in $SYSTEM_BACKUP_FILES; do
        copy_path_preserving_tree "$system_path" "$rootfs_dir" || return 1
    done
    for system_path in ${EXTRA_BACKUP_PATHS:-}; do
        copy_path_preserving_tree "$system_path" "$rootfs_dir" || return 1
    done

    tar -czf "${output_file}.partial" -C "$staging_dir" rootfs inventory || return 1
    tar -tzf "${output_file}.partial" >/dev/null || return 1
    mv -- "${output_file}.partial" "$output_file" || return 1
    rm -rf "$staging_dir"
}

artifact_summary() {
    local artifact="$1"
    local size
    size="$(du -sh "$artifact" | awk '{print $1}')"
    printf '%s (%s)\n' "$artifact" "$size"
}
