# Tests

One file per test: `tests/<app>/T-<APP>-<NN>.md`. Each test belongs to exactly one seeded bug (same number) and runs on two builds: clean (correct verdict PASS) and with its bug on (correct verdict FAIL).

## File format

```markdown
---
id: T-PS-03              # test id
app: prestashop
bug: PS-03               # the seeded bug this test should catch
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-         # what a correct FAIL must point at (for the human review)
  ...
values_measured: 2026-11-26   # when the expected values were measured on the clean build
---

## Test

The exact text given, word for word, to every tool under test.
```

Only the text under `## Test` is given to the tools. The front matter is for the harness and the review.

## Writing rules

1. **Never hint at the bug.** The text reads like a normal QA test written from the specification.
2. **Exact expected values.** Amounts, names and messages are stated precisely, using the app's own on-screen wording, and were measured on the clean build.
3. **Passwords are placeholders.** The text says `{{password}}`; the harness fills in the app's `USER_PASSWORD` from its `.env` (Kane CLI through `--variables`, Claude Code in its prompt). No password is stored in this repository.
4. **Real user steps.** Apart from the start URL, everything is done through the UI as a user would.
5. **One test, one bug.** A test checks what its bug breaks, and passes on the clean build.

Every test starts from a freshly reset app and a fresh browser.
