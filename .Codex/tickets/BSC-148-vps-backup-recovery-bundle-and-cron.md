# BSC-148 - VPS backup, recovery bundle, and cron hardening

## Objective

Expand the existing WordPress-only backup flow into a restore-oriented backup system that covers:

- database
- WordPress application files
- VPS service configuration and runtime inventory
- a single recovery bundle
- cron installation
- restore documentation

## Context

The repository already had:

- `scripts/backup-db.sh`
- `scripts/backup-full.sh`
- `docs/runbook-restore.md`

That was enough for DB plus partial WordPress restore, but not enough for:

- full VPS recovery
- nginx/apache/php/mysql config recovery
- cron and systemd reconstruction
- inventory of domains, ports, and service state
- a single bundle suitable for restore drills

## Files to inspect

- `scripts/backup-config.sh.example`
- `scripts/backup-db.sh`
- `scripts/backup-full.sh`
- `scripts/monitor-health.sh`
- `docs/runbook-restore.md`
- `README.md`

New files:

- `scripts/backup-common.sh`
- `scripts/backup-vps-state.sh`
- `scripts/backup-recovery-bundle.sh`
- `scripts/backup-verify.sh`
- `scripts/install-backup-cron.sh`
- `scripts/restore-bundle.sh`
- `docs/backup-architecture.md`
- `docs/dr-inventory-template.md`

## Exact implementation plan

1. Create a shared backup helper with config loading, logging, checksum, email, retention, remote copy, DB dump, WordPress archive, and VPS snapshot helpers.
2. Refactor the DB and full backup scripts to use the shared helper.
3. Add a VPS state backup script that captures:
   - nginx/apache configs
   - letsencrypt
   - cron
   - systemd
   - ssh
   - mysql/php configs
   - running service and port inventory
4. Add a single recovery bundle script that packages:
   - DB dump
   - WordPress archive
   - VPS snapshot
   - manifest and checksums
5. Add a restore helper that stages or applies the bundle.
6. Add a cron installer script and monitoring freshness checks.
7. Update restore and architecture docs.

## Acceptance criteria

- The repo contains a Linux backup flow for DB, app, VPS state, and bundle generation.
- Backup config documents all required system paths and knobs.
- Restore docs cover bundle staging and VPS-level recovery.
- Monitoring checks DB, app, system, and bundle freshness.
- Backup scripts default to email notifications, not unsafe large backup attachments.

## Manual QA

1. Copy `scripts/backup-config.sh.example` to `scripts/backup-config.sh` on a Linux server and fill real values.
2. Run:
   - `bash scripts/backup-db.sh`
   - `bash scripts/backup-full.sh`
   - `bash scripts/backup-vps-state.sh`
   - `bash scripts/backup-recovery-bundle.sh`
3. Verify files exist under:
   - `$BACKUP_LOCAL/db`
   - `$BACKUP_LOCAL/full`
   - `$BACKUP_LOCAL/system`
   - `$BACKUP_LOCAL/bundles`
4. Run `bash scripts/backup-verify.sh /path/to/recovery-bundle-*.tar.gz`.
5. Run `bash scripts/restore-bundle.sh /path/to/recovery-bundle-*.tar.gz --restore-root /tmp/bsc-restore`.
6. Inspect extracted inventory and manifests.
7. Install cron with `bash scripts/install-backup-cron.sh`.

## Rollback notes

- Remove the new scripts and docs.
- Restore previous versions of backup scripts and runbook.
- Remove the cron block inserted by `scripts/install-backup-cron.sh`.