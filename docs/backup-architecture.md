# BSC backup architecture

Version: 2.0
Last updated: 2026-04-30

## Scope

The backup system is now split into four layers:

1. Database backup
2. WordPress application backup
3. VPS state backup
4. Recovery bundle

This is designed to cover both:

- application-level incidents
- VPS rebuild and migration scenarios

## Backup layers

### 1. DB backup

Script:

- `scripts/backup-db.sh`

Output:

- `db-YYYY-MM-DD-HH-MM.sql.gz`

Contents:

- full MySQL/MariaDB dump

Frequency:

- daily

### 2. WordPress application backup

Script:

- `scripts/backup-full.sh`

Output:

- `full-YYYY-MM-DD-HH-MM.tar.gz`
- `full-YYYY-MM-DD-HH-MM-db.sql.gz`
- `full-YYYY-MM-DD-HH-MM.manifest.txt`

Contents:

- `wp-config.php`
- `wp-content/themes`
- `wp-content/plugins`
- `wp-content/mu-plugins`
- `wp-content/uploads`
- `wp-content/languages`

### 3. VPS state backup

Script:

- `scripts/backup-vps-state.sh`

Output:

- `system-YYYY-MM-DD-HH-MM.tar.gz`

Contents:

- nginx config
- apache/httpd config
- letsencrypt
- cron
- systemd
- ssh
- mysql/php config
- package inventory
- open ports
- running services
- discovered domains, listeners, and roots

### 4. Recovery bundle

Script:

- `scripts/backup-recovery-bundle.sh`

Output:

- `recovery-bundle-YYYY-MM-DD-HH-MM.tar.gz`

Contents:

- DB archive
- WordPress archive
- VPS state archive
- checksums
- bundle manifest

This is the artifact to use for restore drills and off-site retention.

## What this does not cover by itself

These must also exist outside the VPS:

- VPS provider account access
- DNS registrar access
- CDN/WAF access
- SMTP provider access
- payment gateway access
- SSH private keys in a secure password manager

Use:

- `docs/dr-inventory-template.md`

to keep that inventory outside git.

## Email policy

The system sends backup reports and failure alerts by email.

Large backup attachments are disabled by default because:

- they expose sensitive data
- email providers often reject large attachments
- delivery is unreliable as a disaster-recovery transport

If you explicitly enable attachments in `backup-config.sh`, use them only for small artifacts.

Primary off-site retention should be:

- `rsync` to another server
- or an object storage target managed outside the primary VPS provider

## Recommended schedule

- DB backup: daily at 02:00
- VPS state backup: daily at 02:20
- WordPress application backup: daily at 02:40
- Recovery bundle: weekly on Sunday at 03:15
- Health monitor: every 15 minutes

Install with:

```bash
bash scripts/install-backup-cron.sh
```

## Recommended retention

- DB: 30 days
- app backups: 30 days
- VPS state: 30 days
- bundles: 60-90 days if storage allows
- off-site monthly archive: 6-12 months

## Restore strategy

1. Restore DB if data corruption only
2. Restore WordPress archive if application files are damaged
3. Stage VPS state snapshot before applying service config
4. Prefer restore drill in staging before production cutover

See:

- `docs/runbook-restore.md`