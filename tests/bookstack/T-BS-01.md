---
id: T-BS-01
app: bookstack
bug: BS-01
start_url: http://localhost:6875/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  The Books page lists an extra book, "Roadmap", which a Viewer must not see.
values_measured: 2026-11-26
---

## Test

1. Log in with email vera.viewer@bench.local and password {{password}}.
2. Open "Books" from the top navigation.
3. Verify that exactly these four books are listed: "API Guide", "Deployment Runbook", "Onboarding" and "Release Notes". No other book may be listed.
