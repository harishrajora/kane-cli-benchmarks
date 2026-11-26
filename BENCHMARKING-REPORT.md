# Kane CLI Benchmarks — Report

The single source of truth for this benchmark: what was decided, how everything was set up, and the results. Every step is recorded here as it happens. If it is not in this file, it is not part of the benchmark.

**Status (2026-11-26):** Setup in progress. BookStack is running with a benchmark admin account; its seed data is written and awaiting review before loading. PrestaShop, bugs, tests and harness are not started. No benchmark runs yet.

## Contents

1. [Goal and scope](#1-goal-and-scope)
2. [Decisions](#2-decisions)
3. [Environment](#3-environment)
4. [Apps under test](#4-apps-under-test)
5. [Seeded bugs](#5-seeded-bugs)
6. [Tests](#6-tests)
7. [Harness and run settings](#7-harness-and-run-settings)
8. [Results](#8-results)
9. [Work log](#9-work-log)

## 1. Goal and scope

Measure whether a testing agent gives the right verdict. Every test runs against a clean build of an app (correct verdict: PASS) and a bug-seeded build (correct verdict: FAIL).

- **Headline metrics:** false pass rate, false fail rate, bug detection rate by bug type.
- **Also measured:** time per test, steps or turns, consistency across 3–5 repeats, credits or tokens, robustness to page changes.
- **Tools compared:** Kane CLI and Claude Code with Playwright MCP, under identical conditions.
- **Replicability:** only free, open-source components in the benchmark itself; no AI judge. The tools under test need a TestMu account (Kane credits) and a Claude subscription.

The plan, research and rationale were approved by marketing on 2026-11-26: [Kane CLI Benchmark — Plan, Research & Proposal](https://claude.ai/artifact/LRUEp9nZyV4kqWk2nVqAVz).

## 2. Decisions

Newest first.

| Date | Decision | Reason |
| --- | --- | --- |
| 2026-11-26 | BookStack seed size: 6 books, 12 chapters, 31 pages, 5 users (8 books proposed, reduced) | Enough content for real navigation and search, lookalike pages and restricted content, without slowing seeding or runs |
| 2026-11-26 | Seed data is created through BookStack's REST API by a committed script | Rerunnable, and anyone can see exactly what was created |
| 2026-11-26 | Default BookStack admin (`admin@admin.com` / `password`) replaced by `admin@bench.local` with a random password | The public default password should not exist on any instance |
| 2026-11-26 | BookStack pinned at v26.09.3 with MariaDB 11.8.9 (LTS), by tag and digest | Latest stable at setup; native Apple Silicon build; digests guarantee identical images |
| 2026-11-26 | Apps: BookStack + PrestaShop (Conduit dropped) | Best diversity for two apps: wiki with roles vs e-commerce with prices and checkout; both production apps used in published research (WebTestPilot) |
| 2026-11-26 | Two apps, not one | Results from one app could reflect that app rather than the tool |
| 2026-11-26 | Kane CLI upgraded to 0.10.9 (from 0.8.23) | Benchmark the latest release |
| 2026-11-24 | No AI judge; free components only | Anyone can rerun and check the numbers; AI judges are unreliable (AgentRewardBench: none above 70% precision) |
| 2026-11-24 | Head-to-head limited to Claude Code + Playwright MCP | Other agents need paid AI model keys |
| 2026-11-24 | Testing accuracy is the priority; head-to-head in part | Verdict accuracy is what a testing agent is for, and no public benchmark measures it fully |
| — | `kane-cli bugs` is not used | It would give the agent knowledge of the seeded bugs |

## 3. Environment

Recorded 2026-11-26. Update this section whenever anything changes.

### Tools under test

| Tool | Version | Notes |
| --- | --- | --- |
| Kane CLI (`@testmuai/kane-cli`) | 0.10.9 (released 2026-11-24) | Model in config: `v16-alpha`. Models run on TestMu servers and may change independently of the CLI version. |
| Claude Code | 2.1.332 | Playwright MCP version recorded when installed |

### Kane CLI profile at setup

The harness will set these explicitly on every run rather than rely on the profile.

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

### Machine

| Item | Value |
| --- | --- |
| Hardware | Apple M4, 16 GB RAM |
| OS | macOS 26.3.1 |
| Node.js | v24.14.1 |
| Docker | 29.5.3 (VM: 10 CPUs, ~8 GB RAM) |
| Other containers | None running during the benchmark (unrelated `testmuai-pg` and `testmuai-mailpit` stopped on 2026-11-26) |

### Credits

| Date | Kane CLI balance | Note |
| --- | --- | --- |
| 2026-11-26 | 379.37 | At setup, before any benchmark runs |

## 4. Apps under test

### 4.1 BookStack

Wiki and documentation platform ([bookstackapp.com](https://www.bookstackapp.com/)). Content is organised as shelves → books → chapters → pages, with roles and per-item permissions.

| Item | Value |
| --- | --- |
| Version | BookStack v26.09.3 |
| App image | `lscr.io/linuxserver/bookstack:v26.09.3-ls301@sha256:2ab643f9bce3e29b3347b595e369977eea3a40cef72d887fce635faf1cea3abc` |
| Database image | `mariadb:11.8.9@sha256:e1d5495f445654134f226f55a41ce0da227116f6f27f283101f405c794d841fc` |
| URL | `http://localhost:6875` (bound to localhost only) |
| Setup files | `apps/bookstack/docker-compose.yml`, `apps/bookstack/.env.example` |
| Secrets | `apps/bookstack/.env` (gitignored): app key, database passwords, admin password |
| Start | `docker compose up -d` in `apps/bookstack` (about 20 seconds to the login page) |
| Stop | `docker compose stop` in `apps/bookstack` |
| Admin login | `admin@bench.local`; password in `.env` as `ADMIN_PASSWORD` |
| Seed data | Defined in `apps/bookstack/seed/`; not yet loaded (see below) |
| Reset | Not yet created |

How the admin account was set: BookStack has no password-reset command, so user #1 (the built-in admin) was updated in the database with a new email, name and a bcrypt-hashed password. Verified: the new login reaches the home page; `admin@admin.com` / `password` is rejected.

#### Seed data

All content is fictional documentation for a made-up company, "Northwind". It is created by `apps/bookstack/seed/seed.py` through the BookStack REST API, using an admin API token (`benchmark-seed`, expires 2027-11-26; secret in `.env`).

| Item | Count | Details |
| --- | --- | --- |
| Users | 5 | Bench Admin (Admin), Erin Editor and Ethan Editor (Editor), Vera Viewer (Viewer), Hana HR (Viewer + HR) |
| Roles | 3 built-in + 1 custom | Custom role "HR" exists only to unlock the HR Policies book |
| Shelves | 3 | Engineering, Company, Product |
| Books | 6 | API Guide, Deployment Runbook (Engineering); Onboarding, HR Policies (Company); Release Notes, Roadmap (Product) |
| Chapters | 12 | 0–3 per book |
| Pages | 31 | Includes pages directly in a book (no chapter): On-call Contacts, four Release Notes pages |
| Revisions | 3 pages | Rate Limits, Release Checklist, Annual Leave: 3 versions each |
| Images | 3 | Architecture, codebase map, Q1 timeline (generated by `seed/make_assets.py`) |
| Attachments | 2 | `rollback.sh` (Rollback Procedure), `expense-claim-template.csv` (Expense Policy) |
| Tags | 4 names, 12 values | team, audience, status, priority |

**Permissions designed for role bugs:**

| Book | Admin | Editors | Viewer | HR |
| --- | --- | --- | --- | --- |
| HR Policies | Full | Hidden | Hidden | View, create, edit |
| Roadmap | Full | Edit | Hidden | Hidden (via Viewer) |
| All others | Full | Edit | View | View |

**Lookalikes designed to catch wrong-item false passes:**

- "Getting Started" exists in both API Guide and Onboarding, with different content.
- "Release Notes v2.1" and "Release Notes v2.10".
- "Production" (Deployment Runbook) and "Production Access" (Onboarding).

**Content variety:** tables, ordered and bulleted lists, a checklist, code blocks (bash, HTTP, JSON), quotes, images, internal links between pages and books, and concrete facts tests can check (numbers, dates, limits, names).

### 4.2 PrestaShop

Not yet set up.

## 5. Seeded bugs

Not yet designed. Bug types planned (from WebTestPilot's analysis of real GitHub bug reports): missing element, wrong data or calculation, action that does nothing, wrong navigation, shown to the wrong role, validation not enforced. Crashes are excluded.

## 6. Tests

Not yet written.

## 7. Harness and run settings

Not yet built.

## 8. Results

No runs yet.

## 9. Work log

Newest first.

| Date | Step | What was done |
| --- | --- | --- |
| 2026-11-26 | 3 Seed data (prepared) | Created the BookStack API token; wrote `seed/structure.json`, 31 page files plus 6 earlier versions, 3 generated images, 2 attachments and `seed/seed.py`. Not run yet: awaiting review. |
| 2026-11-26 | Housekeeping | Stopped unrelated Docker container `testmuai-pg` (Postgres) to free memory; `testmuai-mailpit` was already stopped. Created this report. |
| 2026-11-26 | 2c.2 Admin account | Replaced the default BookStack admin login; verified new login works and old one fails |
| 2026-11-26 | 2c.1 BookStack running | Wrote Docker setup, generated secrets, started BookStack and MariaDB |
| 2026-11-26 | 2c Version check | Chose BookStack v26.09.3 and MariaDB 11.8.9 from Docker Hub and BookStack's GitHub releases |
| 2026-11-26 | 2a Repo | Created local repo `kane-cli-benchmarks` with folder structure, README and environment record |
| 2026-11-26 | 1 Tools | Upgraded Kane CLI 0.8.23 → 0.10.9; recorded versions, settings, machine and credits |
| 2026-11-26 | Plan approved | Marketing approved the plan document |
| 2026-11-24 | Research | Reviewed academic, vendor and testing-tool benchmarks (see the plan document's Sources) |
