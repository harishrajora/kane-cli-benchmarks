---
id: T-BS-05
app: bookstack
bug: BS-05
start_url: http://localhost:6875/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  Opening Onboarding's "Getting Started" shows the API Guide's page (API base URL and first request) instead of the first-day checklist.
values_measured: 2026-11-26
---

## Test

1. Log in with email vera.viewer@bench.local and password {{password}}.
2. Open the book "Onboarding". In its chapter "First Week", open the page "Getting Started".
3. Verify that the page you are on belongs to the "Onboarding" book.
4. Verify that the page tells new joiners to collect their laptop from the IT desk on floor 3 and to meet their onboarding buddy at 11:00.
