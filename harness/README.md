# Harness

`run.py` runs the tests with both tools and records one JSON line per run. Python standard library only.

```bash
python3 harness/run.py --label smoke --tests T-PS-03 --dry-run   # show the order and commands, run nothing
python3 harness/run.py --label smoke --tests T-PS-03             # 1 test x 2 builds x 2 tools = 4 runs
python3 harness/run.py --label check --tests all                 # 10 tests x 2 x 2 = 40 runs
```

Options: `--tests` (ids or `all`), `--builds clean,bug`, `--tools kane,claude`, `--repeats N`, `--seed N`.

## One run

1. `bugs/apply.sh <app> clean|<bug>` resets the app and confirms its state. If the check fails, the run is recorded as `SETUP_ERROR` and the tool is not started.
2. The tool runs from a new, empty folder outside the repo. Passwords go into files in that folder (Kane CLI: a variables file marked secret; Claude Code: a Playwright MCP secrets file) and the folder is deleted after the run.
3. A line is added to `results/<timestamp>-<label>/runs.jsonl`. Tool output is kept in `results/.../runs/<run-id>/`.

The runner stops before starting if Kane CLI, Claude Code or Playwright MCP differ from the frozen versions in `run.py`.

## Files

| File | What |
| --- | --- |
| `run.py` | The runner |
| `claude-instructions.md` | The only text Claude Code gets besides the test (published in the report) |
| `package.json`, `package-lock.json` | Playwright MCP pinned at 0.0.86 (`npm ci` in this folder installs it) |

## Recorded per run

`verdict` (PASS, FAIL, ERROR, TIMEOUT, SETUP_ERROR, ISOLATION_ERROR), `expected`, `correct`, `seconds`, `steps` (Kane CLI steps or Claude Code turns), `credits` (Kane CLI), `tokens_in`, `tokens_out`, `cost_usd_equivalent` (Claude Code), `reason` (the tool's own words), plus the run's order, seed and setup time.

`ISOLATION_ERROR`: the Claude Code session had a tool other than Playwright MCP. Such a run is not counted.
