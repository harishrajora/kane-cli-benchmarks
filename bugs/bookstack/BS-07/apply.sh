#!/usr/bin/env bash
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
att=$(bs_id attachments name rollback.sh)
target=$(bs_id pages slug incident-response)
bs_api PUT "/attachments/$att" "{\"uploaded_to\":$target}" >/dev/null
