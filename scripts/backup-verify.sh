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

if tar -tzf "$ARTIFACT" >/dev/null 2>&1; then
    echo "Archive integrity OK: $ARTIFACT"
else
    echo "Archive integrity FAILED: $ARTIFACT" >&2
    exit 1
fi

CHECKSUM_FILE="${ARTIFACT}.sha256"
if [ -f "$CHECKSUM_FILE" ]; then
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum -c "$CHECKSUM_FILE"
    elif command -v shasum >/dev/null 2>&1; then
        shasum -a 256 -c "$CHECKSUM_FILE"
    else
        echo "No checksum command available; checksum not verified"
    fi
else
    echo "Checksum file not found: $CHECKSUM_FILE"
fi