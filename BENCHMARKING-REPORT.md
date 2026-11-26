# Kane CLI Benchmarks — Report

The single source of truth for this benchmark: what was decided, how everything was set up, and the results. Every step is recorded here as it happens. If it is not in this file, it is not part of the benchmark.

**Status (2026-11-26):** Setup in progress. BookStack is running, seeded and verified, with a 1–2 second reset. PrestaShop 9.2.1 is installed and running with a benchmark admin account; its seed data is not designed yet. Bugs, tests and harness are not started. No benchmark runs yet.

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
| 2026-11-26 | PrestaShop seed: `seed.php` using PrestaShop's own classes, not raw SQL or the Webservice API | Raw SQL bypasses validation and could plant accidental bugs that skew false-fail counts; the Webservice API cannot create reliable orders or staff permissions and would need switching on |
| 2026-11-26 | PrestaShop seed: cart scenarios included (saved cart, quantity rules, vouchers); no minimum order amount | The cart is where totals are calculated, the core of false-pass checks |
| 2026-11-26 | Keep PrestaShop's 5 demo orders; minimum (not maximum) quantity rule | No supported order deletion; no built-in maximum quantity |
| 2026-11-26 | PrestaShop pinned at 9.2.1 (default theme Hummingbird) with MySQL 8.4.13 (LTS), by tag and digest; back office fixed at `/admin-bench` | Latest stable; native Apple Silicon build; MySQL is PrestaShop's recommended database; a fixed back-office URL keeps tests identical across machines |
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
| Benchmark containers, idle memory | BookStack 71 MB + MariaDB 166 MB; PrestaShop 297 MB + MySQL 529 MB (about 1.1 GB of Docker's ~8 GB) |

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
| Seed data | Loaded 2026-11-26 by `apps/bookstack/seed/seed.py` (see below) |
| Reset | `apps/bookstack/reset.sh` restores the post-seed snapshot in 1–2 seconds (see below) |

How the admin account was set: BookStack has no password-reset command, so user #1 (the built-in admin) was updated in the database with a new email, name and a bcrypt-hashed password. Verified: the new login reaches the home page; `admin@admin.com` / `password` is rejected.

#### Seed data

All content is fictional documentation for a made-up company, "Northwind". It is created by `apps/bookstack/seed/seed.py` through the BookStack REST API, using an admin API token (`benchmark-seed`, expires 2027-11-26; secret in `.env`).

| Item | Count | Details |
| --- | --- | --- |
| Users | 5 | Bench Admin (Admin), Erin Editor and Ethan Editor (Editor), Vera Viewer (Viewer), Hana HR (Viewer + HR Team) |
| Roles | 3 built-in + 1 custom | Custom role "HR Team" exists only to unlock the HR Policies book |
| Shelves | 3 | Engineering, Company, Product |
| Books | 6 | API Guide, Deployment Runbook (Engineering); Onboarding, HR Policies (Company); Release Notes, Roadmap (Product) |
| Chapters | 12 | 0–3 per book |
| Pages | 31 | Includes pages directly in a book (no chapter): On-call Contacts, four Release Notes pages |
| Revisions | 3 pages | Rate Limits, Release Checklist, Annual Leave: 3 versions each |
| Images | 3 | Architecture, codebase map, Q1 timeline (generated by `seed/make_assets.py`) |
| Attachments | 2 | `rollback.sh` (Rollback Procedure), `expense-claim-template.csv` (Expense Policy) |
| Tags | 4 names, 12 values | team, audience, status, priority |

**Permissions designed for role bugs:**

| Book | Admin | Editors | Viewer | HR Team |
| --- | --- | --- | --- | --- |
| HR Policies | Full | Hidden | Hidden | View, create, edit |
| Roadmap | Full | Edit | Hidden | Hidden (via Viewer) |
| All others | Full | Edit | View | View |

**Lookalikes designed to catch wrong-item false passes:**

- "Getting Started" exists in both API Guide and Onboarding, with different content.
- "Release Notes v2.1" and "Release Notes v2.10".
- "Production" (Deployment Runbook) and "Production Access" (Onboarding).

**Content variety:** tables, ordered and bulleted lists, a checklist, code blocks (bash, HTTP, JSON), quotes, images, internal links between pages and books, and concrete facts tests can check (numbers, dates, limits, names).

**Seeding run (2026-11-26):** 19 seconds. The first attempt stopped at the first call because BookStack requires role names of at least 3 characters; the custom role was renamed from "HR" to "HR Team" and the seed rerun on the still-empty instance.

**Verification:**

| Check | Result |
| --- | --- |
| Counts via API | 3 shelves, 6 books, 12 chapters, 31 pages, 2 attachments, 3 gallery images; 6 users (5 seeded + built-in Guest); 5 roles (4 built-in + HR Team) — all as planned |
| Pages directly in a book | On-call Contacts and four Release Notes pages have no chapter, as planned |
| Revisions | Rate Limits, Release Checklist, Annual Leave: 3 revisions each |
| Lookalike slugs | `release-notes-v210` and `release-notes-v21` are distinct |
| Book visibility per user (logged in via the web) | Admin: all 6. Erin, Ethan: all except HR Policies. Vera: all except HR Policies and Roadmap. Hana: all except Roadmap. Matches the permissions table. |
| Direct URLs to hidden content | Vera gets HTTP 404 on Roadmap and HR Policies pages, even by typing the URL |
| Internal links and images | 16 links checked (3 of them images): 0 broken; no unresolved placeholders |

Note: BookStack fetched an avatar for each seeded user from Gravatar (an outbound request using the fictional `@bench.local` email hashes). The avatars are saved in the snapshot, so resets do not repeat it.

#### Reset

| Script | What it does |
| --- | --- |
| `apps/bookstack/snapshot.sh` | Run once after seeding. Saves the database dump and uploaded files to `apps/bookstack/snapshot/` (gitignored: it contains password hashes tied to this machine's `.env`; anyone rerunning the benchmark seeds and snapshots their own instance). |
| `apps/bookstack/reset.sh` | Drops and restores the database, restores uploaded images and attachments, clears the cache and deletes all login sessions. Run before every test. |

Snapshot taken 2026-11-26: 219 KB of SQL, 199 KB of files.

**Reset test:** renamed a page, deleted an attachment and added a book; after `reset.sh` all three were back to the seeded state. Three more resets ran in 1–2 seconds each. After reset, Vera and Admin log in normally, both attachments download, and images load.

### 4.2 PrestaShop

E-commerce platform ([prestashop.com](https://prestashop.com/)): catalogue, cart, checkout, customer accounts, and a back office for orders, products and prices.

| Item | Value |
| --- | --- |
| Version | PrestaShop 9.2.1 (latest stable, released 2026-11-09), default front-office theme Hummingbird |
| App image | `prestashop/prestashop:9.2.1-apache@sha256:ad8ce05927bb28f5fb4da08a278dea43e10a1fed032e666baa27214303cfc8c9` |
| Database image | `mysql:8.4.13@sha256:8c233bad33d520a7aae1c1e619c3a9775f4a1ba2d463d692fada04ec7f40be00` (LTS; PrestaShop's recommended database) |
| Shop URL | `http://localhost:8080` (bound to localhost only) |
| Back office | `http://localhost:8080/admin-bench/` |
| Setup files | `apps/prestashop/docker-compose.yml`, `apps/prestashop/.env.example`, `apps/prestashop/post-install.sh` |
| Secrets | `apps/prestashop/.env` (gitignored): database passwords, admin password |
| First start | `docker compose up -d`, wait for the automatic install (about 40 seconds), then `./post-install.sh` once |
| Stop | `docker compose stop` in `apps/prestashop` |
| Admin login | `admin@bench.local`; password in `.env` as `ADMIN_PASSWORD`. Set at install, so PrestaShop's public demo admin login never exists. |
| Install settings | Language English, country United States, demo products installed, SSL off |
| Seed data | Defined in `apps/prestashop/seed/`; not yet loaded (see below) |
| Reset | Not yet created |

**Install issue and fix:** the image's option to rename the back-office folder (`PS_FOLDER_ADMIN`) breaks PrestaShop 9.2.1's installer: the installer expects the folder to still be called `admin` and fails with "The target directory /var/www/html/admin-dev does not exist". The fix leaves the folder alone during install (the installer renames it to a random name), then `post-install.sh` renames it to the fixed `admin-bench` and clears the cache, so the back-office URL is identical on every machine.

**Verified:** shop home page and back-office login page return HTTP 200; the installer folder is gone (404); `admin@bench.local` logs in to the dashboard; the public demo login `demo@prestashop.com` is rejected.

**Demo data present after install:** 19 products, 9 categories, 5 sample orders, the demo customer `pub@prestashop.com` (its password is published in PrestaShop's documentation; to be dealt with in the seed step) and an inactive GDPR placeholder customer.

**Memory use (idle):** PrestaShop 297 MB, MySQL 529 MB.

#### Seed data

Applied by `apps/prestashop/seed.sh`, which runs `seed/seed.php` inside the container. The script uses PrestaShop's own classes (the code the back office runs), so values are validated and related data is filled in as if a merchant entered them. No raw SQL, and the Webservice API stays switched off. The data is in `seed/structure.json`.

| Area | Seeded |
| --- | --- |
| Shop | Name "Northwind Store"; saved cart restored on login (`PS_CART_FOLLOWING`); outgoing email switched off |
| Products | The 19 demo products and 9 categories, prices as installed |
| Stock and quantity rules | "Mug Today is a good day": out of stock (orders refused). "Mug The adventure begins": 3 left. "Mug The best is yet to come": minimum quantity 2 |
| Tax | One rule, "Bench Sales Tax (8%)", for every US state, on all 19 products (replaces the installer's per-state rules, which applied Florida 6% to all products) |
| Shipping | Existing carriers disabled. Standard: $5, free from $50. Express: $15. Shipping not taxed |
| Vouchers | `BENCH10` 10% off; `SAVE5` $5 off orders over $30 (before tax); `EXPIRED` 15% off, ended 2026-06-30 |
| Customer groups | Customer (built in); Wholesale, 15% off all products |
| Customers | Casey Customer (Springfield, IL; Customer group); Wes Wholesale (Portland, OR; Wholesale group). Demo customer `pub@prestashop.com` deactivated with a random password |
| Orders | Casey: 1 Delivered (Standard), 1 Shipped (Express). Wes: 1 Awaiting bank wire payment (Standard). Created through a real cart and checkout so totals are computed by PrestaShop. The 5 demo orders are kept on the deactivated demo customer |
| Saved cart | Casey: 2 × Hummingbird printed t-shirt (L, Black) and 1 × Mug The adventure begins |
| Back-office staff | Bench Admin (SuperAdmin); Sam Sales (built-in Salesman profile) |

**Why the demo orders are kept:** PrestaShop has no supported way to delete orders. The base delete removes only the main order row and leaves line items, history and invoices behind, which would make the data inconsistent. Kept on the deactivated demo customer, they act as other customers' orders in the back office.

**Why minimum quantity instead of maximum:** PrestaShop has no built-in maximum quantity per product (it needs an add-on module); a built-in minimum quantity gives the same kind of quantity-validation bug.

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
| 2026-11-26 | PrestaShop seed (prepared) | Wrote `seed/structure.json`, `seed/seed.php` and `seed.sh`; syntax-checked with the container's PHP. Not run yet: awaiting review. |
| 2026-11-26 | 2d PrestaShop running | Chose PrestaShop 9.2.1 + MySQL 8.4.13; first auto-install failed (admin folder renamed too early), removed it and its volumes, reinstalled with the fix and `post-install.sh`; verified shop, back office and admin login |
| 2026-11-26 | 3 Seed, verify, reset | Seeded BookStack (role renamed HR → HR Team after a validation error); verified counts, permissions per user, links and images; took the snapshot; wrote and tested `reset.sh`. Screenshots skipped: the Chrome extension was not connected. |
| 2026-11-26 | 3 Seed data (prepared) | Created the BookStack API token; wrote `seed/structure.json`, 31 page files plus 6 earlier versions, 3 generated images, 2 attachments and `seed/seed.py`. |
| 2026-11-26 | Housekeeping | Stopped unrelated Docker container `testmuai-pg` (Postgres) to free memory; `testmuai-mailpit` was already stopped. Created this report. |
| 2026-11-26 | 2c.2 Admin account | Replaced the default BookStack admin login; verified new login works and old one fails |
| 2026-11-26 | 2c.1 BookStack running | Wrote Docker setup, generated secrets, started BookStack and MariaDB |
| 2026-11-26 | 2c Version check | Chose BookStack v26.09.3 and MariaDB 11.8.9 from Docker Hub and BookStack's GitHub releases |
| 2026-11-26 | 2a Repo | Created local repo `kane-cli-benchmarks` with folder structure, README and environment record |
| 2026-11-26 | 1 Tools | Upgraded Kane CLI 0.8.23 → 0.10.9; recorded versions, settings, machine and credits |
| 2026-11-26 | Plan approved | Marketing approved the plan document |
| 2026-11-24 | Research | Reviewed academic, vendor and testing-tool benchmarks (see the plan document's Sources) |
