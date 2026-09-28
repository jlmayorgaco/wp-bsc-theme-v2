#!/bin/bash
# BSC-148: Verify integrity of a recovery bundle or standalone backup artifact.
# Usage:
#   bash scripts/backup-verify.sh /path/to/recovery-bundle-2026-04-30-03-00.tar.gz

set -euo pipefail

if [ "$#" -lt 1 ]; then
    echo "Usage: bash scripts/backup-verify.sh /path/to/backup-artifact" >&2
    exit 1
fi

ARTIFACT="$1"

if [ ! -f "$ARTIFACT" ]; then
    echo "Artifact not found: $ARTIFACT" >&2
    exit 1
fi

CHECKSUM_FILE="${ARTIFACT}.sha256"
if [ ! -f "$CHECKSUM_FILE" ]; then
    echo "Missing checksum: $CHECKSUM_FILE" >&2
    exit 1
fi
# Read only the digest, never follow a pathname supplied by a checksum file.
EXPECTED="$(awk 'NR==1 {print $1}' "$CHECKSUM_FILE")"
if [[ ! "$EXPECTED" =~ ^[0-9a-fA-F]{64}$ ]]; then
    echo "Invalid SHA256 digest" >&2
    exit 1
fi
if command -v sha256sum >/dev/null 2>&1; then
    ACTUAL="$(sha256sum -- "$ARTIFACT" | awk '{print $1}')"
elif command -v shasum >/dev/null 2>&1; then
    ACTUAL="$(shasum -a 256 -- "$ARTIFACT" | awk '{print $1}')"
else
    echo "SHA256 utility required" >&2
    exit 1
fi
if [ "${EXPECTED,,}" != "${ACTUAL,,}" ]; then
    echo "Checksum FAILED: $ARTIFACT" >&2
    exit 1
fi
case "$ARTIFACT" in
    *.sql.gz) gzip -t -- "$ARTIFACT" ;;
    *.tar.gz|*.tgz) tar -tzf "$ARTIFACT" >/dev/null ;;
    *.age) ;; # Integrity verified here; decrypt and verify inner files before restoring.
    *) echo "Unsupported artifact type: $ARTIFACT" >&2; exit 1 ;;
esac
echo "Integrity OK: $ARTIFACT"
