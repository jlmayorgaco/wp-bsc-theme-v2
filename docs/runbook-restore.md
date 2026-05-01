# BSC-046 / BSC-148 - Restore runbook

Version: 2.0
Last updated: 2026-04-30
Estimated RTO: 30-90 minutes
Estimated RPO: 24 hours minimum

---

## Preconditions

- [ ] SSH access to the server
- [ ] Sudo access if system config must be applied
- [ ] Database credentials available in `backup-config.sh`
- [ ] Access to `/backups/bsc/` or the off-site destination
- [ ] WP-CLI installed
- [ ] `mysql`, `tar`, and `rsync` available

---

## Restore paths now available

There are four backup classes:

1. DB backup: `/backups/bsc/db/`
2. WordPress app backup: `/backups/bsc/full/`
3. VPS state backup: `/backups/bsc/system/`
4. Recovery bundle: `/backups/bsc/bundles/`

If possible, restore from the recovery bundle first.

---

## Scenario 1: Restore only the database

Use when code and files are fine but data is corrupted or missing.

```bash
wp maintenance-mode activate --path=/var/www/html
ls -lh /backups/bsc/db/
gunzip -c /backups/bsc/db/db-DATE.sql.gz | mysql -h DB_HOST -u DB_USER -p DB_NAME
mysql -h DB_HOST -u DB_USER -p DB_NAME -e "SHOW TABLES;" | head -20
wp cache flush --path=/var/www/html
wp transient delete --all --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Scenario 2: Restore WordPress application files

Use when WordPress files, uploads, plugins, or theme are damaged.

```bash
wp maintenance-mode activate --path=/var/www/html
cd /var/www/html
ls -lh /backups/bsc/full/
tar -xzf /backups/bsc/full/full-DATE.tar.gz -C /var/www/html
chown -R www-data:www-data /var/www/html/wp-content
chmod -R 755 /var/www/html/wp-content
find /var/www/html/wp-content -type f -name "*.php" -exec chmod 644 {} \;
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Scenario 3: Stage a full recovery bundle

Use before any destructive restore so you can inspect the bundle.

```bash
bash scripts/restore-bundle.sh /backups/bsc/bundles/recovery-bundle-DATE.tar.gz --restore-root /tmp/bsc-restore
```

That will extract:

- DB archive
- WordPress archive
- VPS state snapshot
- manifest
- checksums

Review:

- `/tmp/bsc-restore/bundle/bundle-manifest.txt`
- `/tmp/bsc-restore/system-stage/inventory/`

---

## Scenario 4: Full application restore from bundle

Use when the site must be rebuilt on the same server or on a fresh VPS.

```bash
wp maintenance-mode activate --path=/var/www/html

bash scripts/restore-bundle.sh \
  /backups/bsc/bundles/recovery-bundle-DATE.tar.gz \
  --restore-root /tmp/bsc-restore \
  --apply-db \
  --apply-wordpress \
  --wp-root /var/www/html

chown -R www-data:www-data /var/www/html/wp-content
chmod -R 755 /var/www/html/wp-content
wp cache flush --path=/var/www/html
wp rewrite flush --path=/var/www/html
wp maintenance-mode deactivate --path=/var/www/html
```

---

## Scenario 5: Apply VPS config from staged system snapshot

Use only after reviewing the staged files.

```bash
bash scripts/restore-bundle.sh \
  /backups/bsc/bundles/recovery-bundle-DATE.tar.gz \
  --restore-root /tmp/bsc-restore \
  --apply-system-config
```

This copies the staged `rootfs` overlay into `/`.

Review first:

- nginx/apache configs
- SSL paths
- cron files
- ssh config
- php/mysql config

After that, validate and restart services as needed:

```bash
nginx -t
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart mysql
```

Service names will vary by server.

---

## Scenario 6: Restore on a new VPS

Use after total VPS loss or account loss at the provider.

1. Provision a clean Linux VPS.
2. Install required runtime:
   - nginx or apache
   - PHP
   - MySQL/MariaDB
   - WP-CLI
3. Copy the recovery bundle from off-site storage.
4. Stage the bundle:

```bash
bash scripts/restore-bundle.sh /path/to/recovery-bundle-DATE.tar.gz --restore-root /tmp/bsc-restore
```

5. Review staged inventory and service config.
6. Apply DB and WordPress.
7. Apply system config only after validating environment differences.
8. Update DNS if IP changed.
9. Validate SSL, cron, email, checkout, and admin access.

---

## Restore in staging first

Always prefer a staging restore drill before production if time allows.

```bash
rsync -az /backups/bsc/bundles/recovery-bundle-DATE.tar.gz staging:/tmp/
ssh staging
bash /var/www/html/wp-content/themes/wp-bsc-theme-v2/scripts/restore-bundle.sh /tmp/recovery-bundle-DATE.tar.gz --restore-root /tmp/bsc-restore
```

---

## Post-restore validation checklist

- [ ] Home loads without PHP warnings
- [ ] Navigation works on desktop and mobile
- [ ] Product images load
- [ ] Add-to-cart works
- [ ] Cart shows correct quantities
- [ ] Coupon apply/remove works
- [ ] Shipping totals recalculate correctly
- [ ] Checkout works
- [ ] Thank You page renders correctly
- [ ] `/wp-admin/` works
- [ ] WooCommerce shows historical orders
- [ ] SSL is valid
- [ ] Cron is installed and visible
- [ ] Backup directories are writable
- [ ] Transactional email can be sent
- [ ] BSC admin screens load

---

## Operational note

The backup system can inventory domains, listeners, roots, ports, and server config from the VPS.

It cannot back up third-party accounts by itself:

- VPS control panel account
- DNS registrar account
- CDN/WAF account
- payment gateway account
- SMTP provider account

Track those in:

- `docs/dr-inventory-template.md`
