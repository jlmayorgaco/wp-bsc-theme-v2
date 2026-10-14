#!/bin/bash
# Synthetic artifacts only. Does not load credentials, connect remotely or apply a restore.
set -euo pipefail
REPO="$1"
TESTDIR="$2"
mkdir -p "$TESTDIR/original" "$TESTDIR/relocated" "$TESTDIR/app" "$TESTDIR/system/rootfs" "$TESTDIR/system/inventory" "$TESTDIR/bundle" "$TESTDIR/stage"
source "$REPO/scripts/backup-common.sh"
LOG_FILE="$TESTDIR/backup-test.log"
ADMIN_EMAIL=""
DB_HOST="localhost"; DB_USER="synthetic"; DB_PASS="synthetic"; DB_NAME="synthetic"
WP_ROOT="$TESTDIR/app"; BACKUP_LOCAL="$TESTDIR/backups"
WORDPRESS_BACKUP_PATHS="."; WORDPRESS_EXCLUDES="wp-content/cache"
printf '<?php // synthetic core\n' > "$WP_ROOT/wp-load.php"
printf 'private configuration\n' > "$WP_ROOT/.env"
mysqldump() { printf 'CREATE TABLE synthetic (id int); INSERT INTO synthetic VALUES (1);\n'; }
backup_database "$TESTDIR/original/db-test.sql.gz"
write_checksum_file "$TESTDIR/original/db-test.sql.gz"
cp "$TESTDIR/original/db-test.sql.gz" "$TESTDIR/relocated/db-test.sql.gz"
cp "$TESTDIR/original/db-test.sql.gz.sha256" "$TESTDIR/relocated/db-test.sql.gz.sha256"
mv "$TESTDIR/original/db-test.sql.gz" "$TESTDIR/original/moved.sql.gz"
bash "$REPO/scripts/backup-verify.sh" "$TESTDIR/relocated/db-test.sql.gz"
echo 'PASS relocated SQL checksum and gzip verification'
cp "$TESTDIR/relocated/db-test.sql.gz" "$TESTDIR/relocated/broken.sql.gz"
cp "$TESTDIR/relocated/db-test.sql.gz.sha256" "$TESTDIR/relocated/broken.sql.gz.sha256"
printf 'corruption' >> "$TESTDIR/relocated/broken.sql.gz"
if bash "$REPO/scripts/backup-verify.sh" "$TESTDIR/relocated/broken.sql.gz"; then exit 1; fi
echo 'PASS corrupted backup rejected'
REMOTE_DEST=''; REMOTE_REQUIRED=1
if push_remote_artifacts db "$TESTDIR/relocated/db-test.sql.gz"; then exit 1; fi
echo 'PASS missing remote fails closed'
rsync() { return 42; }
REMOTE_DEST='synthetic@invalid.test:/backups'; REMOTE_REQUIRE_ENCRYPTION=0
if push_remote_artifacts db "$TESTDIR/relocated/db-test.sql.gz"; then exit 1; fi
echo 'PASS failed remote transfer propagates failure'
REMOTE_REQUIRE_ENCRYPTION=1; AGE_RECIPIENT=''
if push_remote_artifacts db "$TESTDIR/relocated/db-test.sql.gz"; then exit 1; fi
echo 'PASS missing encryption recipient fails closed'
mysqldump() { return 43; }
if backup_database "$TESTDIR/original/failed.sql.gz"; then exit 1; fi
test ! -f "$TESTDIR/original/failed.sql.gz"
echo 'PASS failed dump never publishes a complete artifact'
backup_wordpress_application "$TESTDIR/bundle/wordpress-test.tar.gz"
tar -tzf "$TESTDIR/bundle/wordpress-test.tar.gz" | grep -F './.env' >/dev/null
echo 'PASS WordPress core and dotfiles included'
cp "$TESTDIR/relocated/db-test.sql.gz" "$TESTDIR/bundle/db-test.sql.gz"
tar -czf "$TESTDIR/bundle/system-test.tar.gz" -C "$TESTDIR/system" rootfs inventory
for file in "$TESTDIR/bundle/"*; do write_checksum_file "$file"; done
tar -czf "$TESTDIR/recovery-bundle-test.tar.gz" -C "$TESTDIR/bundle" .
write_checksum_file "$TESTDIR/recovery-bundle-test.tar.gz"
bash "$REPO/scripts/restore-bundle.sh" "$TESTDIR/recovery-bundle-test.tar.gz" --restore-root "$TESTDIR/stage"
echo 'PASS recovery bundle stages without credentials or writes to a live destination'
mkdir -p "$TESTDIR/failing-network"
printf '#!/bin/bash\nexit 7\n' > "$TESTDIR/failing-network/curl"
printf '#!/bin/bash\nexit 1\n' > "$TESTDIR/failing-network/openssl"
chmod +x "$TESTDIR/failing-network/curl" "$TESTDIR/failing-network/openssl"
cat > "$TESTDIR/monitor-config.sh" <<EOF
DB_HOST=localhost
DB_NAME=synthetic
DB_USER=synthetic
DB_PASS=synthetic
WP_ROOT='$WP_ROOT'
BACKUP_LOCAL='$BACKUP_LOCAL'
LOG_FILE='$LOG_FILE'
SITE_URL=https://invalid.test
ADMIN_EMAIL=''
EOF
if PATH="$TESTDIR/failing-network:$PATH" BSC_BACKUP_CONFIG="$TESTDIR/monitor-config.sh" bash "$REPO/scripts/monitor-health.sh"; then exit 1; fi
grep -F 'connection-failed' "$LOG_FILE" >/dev/null
grep -F 'Could not retrieve certificate' "$LOG_FILE" >/dev/null
echo 'PASS health monitor reports network and SSL failures instead of exiting silently'
if copy_path_preserving_tree "$TESTDIR/app/wp-load.php" "$TESTDIR/system/rootfs"; then exit 1; fi
echo 'PASS system file copy propagates rsync failure'
SYSTEM_BACKUP_ROOTS=''; SYSTEM_BACKUP_FILES=''; EXTRA_BACKUP_PATHS=''
write_system_inventory() { return 0; }
tar() { return 42; }
if backup_vps_state "$TESTDIR/system/failed.tar.gz"; then exit 1; fi
test ! -f "$TESTDIR/system/failed.tar.gz"
echo 'PASS failed system archive never reports success from an if condition'
