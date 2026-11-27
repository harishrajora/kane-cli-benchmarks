# Seeded bugs

Each bug is one folder: `bugs/<app>/<BUG-ID>/`.

| File | Purpose |
| --- | --- |
| `bug.json` | ID, type, severity, title, what a correct test sees on the clean build, what it sees with the bug, the test it belongs to, and how it is switched on |
| `apply.sh` | Switches the bug on (run after the app's reset) |
| `check.sh` | Confirms the bug is actually active; exits non-zero if not |
| other files | What `apply.sh` installs, e.g. a BookStack theme `functions.php` |

## Using a bug

```bash
bugs/apply.sh bookstack clean     # reset; confirm the clean build
bugs/apply.sh bookstack BS-01     # reset; switch BS-01 on; confirm it is active
```

`apply.sh` always resets first, so only one bug is ever active, and refuses to continue if the requested state cannot be confirmed. The harness calls it before every run.

## How bugs are switched on

Only the apps' official customisation points are used; the apps' own code is never edited.

| App | Mechanism | Clean build |
| --- | --- | --- |
| BookStack | Settings changed through the REST API, or a theme `functions.php` using BookStack's documented theme events | Theme folder `bench` is empty |
| PrestaShop | Settings changed through PrestaShop's own classes, or official overrides | No overrides |

Every bug is removed by the app's `reset.sh`.

## Held-out variants

About a quarter of the bugs also have a private variant (same type and test, different details) that is not in this repository. See the report for how they are used.
