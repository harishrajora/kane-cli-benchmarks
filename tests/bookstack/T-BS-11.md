---
id: T-BS-11
app: bookstack
bug: BS-11
start_url: http://localhost:6875/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  The page is saved even though its title is empty; no "The name field is required." error appears.
values_measured: 2026-11-26
---

## Test

1. Log in with email erin.editor@bench.local and password {{password}}.
2. Open the book "Onboarding" and create a new page in it.
3. Leave the page title empty. Type "Draft content" in the page body. Click "Save Page".
4. Verify that the page is not saved: the editor stays open and shows the error "The name field is required."
