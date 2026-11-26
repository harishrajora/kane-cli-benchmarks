# Environment

Recorded 2026-11-26. Every published result states the environment it ran on; update this file whenever any of it changes.

## Tools under test

| Tool | Version | Notes |
| --- | --- | --- |
| Kane CLI (`@testmuai/kane-cli`) | 0.10.9 (released 2026-11-24) | Model reported by config: `v16-alpha`. Models run on TestMu servers and may change independently of the CLI version. |
| Claude Code | 2.1.332 | With Playwright MCP (version recorded when installed) |

## Kane CLI settings at setup

These affect results. The harness will set them explicitly per run rather than rely on this profile.

| Setting | Value |
| --- | --- |
| mode | testing |
| assertion_mode | dom |
| final_validation | false |
| bug_detection | off |
| assertion_failure_mode | stop |
| window_size | 1920x1080 |
| target | desktop |
| env | prod |
| auth | oauth |

Not used in this benchmark: `kane-cli bugs` (would give the agent knowledge of seeded bugs).

## Machine

| Item | Value |
| --- | --- |
| Hardware | Apple M4, 16 GB RAM |
| OS | macOS 26.3.1 |
| Node.js | v24.14.1 |
| Docker | 29.5.3 (VM: 10 CPUs, ~8 GB RAM) |

## Apps under test

| App | Version | Image digest | Port |
| --- | --- | --- | --- |
| BookStack | TBD | TBD | TBD |
| PrestaShop | TBD | TBD | TBD |

## Credits

Kane CLI balance at setup: 379.37 credits.
