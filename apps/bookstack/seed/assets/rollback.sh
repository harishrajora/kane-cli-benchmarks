#!/usr/bin/env bash
# Roll production back to a previous release tag (fictional example).
# Usage: ./rollback.sh v2.9.3
set -euo pipefail
echo "Rolling back to ${1:?release tag required}"
