---
id: T-BS-03
app: bookstack
bug: BS-03
start_url: http://localhost:6875/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  After saving, the page title is still "Glossary" (the change was not saved), even though a success message was shown.
values_measured: 2026-11-26
---

## Test

1. Log in with email erin.editor@bench.local and password {{password}}.
2. Open the book "Onboarding", then open the page "Glossary".
3. Edit the page: change its title from "Glossary" to "Glossary of Terms", then save the page.
4. Verify that the page now shows the title "Glossary of Terms".
5. Open the book "Onboarding" and verify that its contents list a page named "Glossary of Terms" and no page named "Glossary".
