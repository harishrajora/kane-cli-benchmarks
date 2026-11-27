---
id: T-BS-07
app: bookstack
bug: BS-07
start_url: http://localhost:6875/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  The Rollback Procedure page has no attachment named rollback.sh (the text mentioning the file is not an attachment).
values_measured: 2026-11-26
---

## Test

1. Log in with email vera.viewer@bench.local and password {{password}}.
2. Open the book "Deployment Runbook", then open the page "Rollback Procedure".
3. Verify that the page has a file attachment named "rollback.sh" in its attachments list.
