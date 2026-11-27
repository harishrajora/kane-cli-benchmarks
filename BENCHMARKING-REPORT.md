# Kane CLI Benchmarks — Report

The single source of truth for this benchmark: what was decided, how everything was set up, and the results. Every step is recorded here as it happens. If it is not in this file, it is not part of the benchmark.

**Status (2026-11-26):** Setup in progress. BookStack is running, seeded and verified, with a 1–2 second reset. PrestaShop 9.2.1 is running, seeded and verified to the cent, with a 5–6 second reset. Bug design done (30 bugs); bug framework and all 10 pilot bugs (5 per app) built and verified; the 10 pilot tests written with measured values. The harness is not started. No benchmark runs yet.

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
| 2026-11-26 | Pilot of 10 bugs first, for analysis only; the report's metrics come from the full 30-bug run | Check cost and the harness before building everything |
| 2026-11-26 | Held-out variants for about 8 of the 30 bugs | Prevents tuning to the public bug list; a credibility check |
| 2026-11-26 | 30 bugs (15 per app), one test each; silent failure added as a 7th type | Covers all types on both apps; silent failure is the strongest false-pass trap |
| 2026-11-26 | Bugs switched on through official customisation points (API settings, BookStack theme events, PrestaShop overrides), not JavaScript injection | Server-side bugs behave like real bugs everywhere; the apps' own code stays untouched |
| 2026-11-26 | Disable PrestaShop's seven cloud and marketing modules (not uninstall) | Runs must not depend on outside services; avoids false fails from slow or failing third-party calls; checkout shows only built-in payments |
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
| Seed data | Loaded 2026-11-26 by `apps/prestashop/seed.sh` (see below) |
| Reset | `apps/prestashop/reset.sh` restores the post-seed snapshot in 5–6 seconds (see below) |
| Rebuild from scratch | `docker compose down -v`, `docker compose up -d`, wait for the install, `./post-install.sh`, `./seed.sh`, `./snapshot.sh` (about 2 minutes) |

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
| Shipping | Existing carriers disabled. Standard: $5, free from $50 **after tax** (measured). Express: $15. Shipping not taxed |
| Vouchers | `BENCH10` 10% off; `SAVE5` $5 off orders over $30 (before tax); `EXPIRED` 15% off, ended 2026-06-30 |
| Customer groups | Customer (built in); Wholesale, 15% off all products |
| Customers | Casey Customer (Springfield, IL; Customer group); Wes Wholesale (Portland, OR; Wholesale group). Demo customer `pub@prestashop.com` deactivated with a random password |
| Orders | Casey: 1 Delivered (Standard), 1 Shipped (Express). Wes: 1 Awaiting bank wire payment (Standard). Created through a real cart and checkout so totals are computed by PrestaShop. The 5 demo orders are kept on the deactivated demo customer |
| Saved cart | Casey: 2 × Hummingbird printed t-shirt (L, Black) and 1 × Mug The adventure begins |
| Back-office staff | Bench Admin (SuperAdmin); Sam Sales (built-in Salesman profile) |

**Why the demo orders are kept:** PrestaShop has no supported way to delete orders. The base delete removes only the main order row and leaves line items, history and invoices behind, which would make the data inconsistent. Kept on the deactivated demo customer, they act as other customers' orders in the back office.

**Why minimum quantity instead of maximum:** PrestaShop has no built-in maximum quantity per product (it needs an add-on module); a built-in minimum quantity gives the same kind of quantity-validation bug.

**Demo discounts already in the catalogue:** "Hummingbird printed sweater" and "Hummingbird printed t-shirt" carry a 20% demo price reduction (sweater $35.90 → $28.72, t-shirt $23.90 → $19.12). They were left as installed; tests must expect the reduced prices.

**Order references are random** (e.g. `NQYIYPSSO`): PrestaShop generates them, so they differ on every rebuild. Tests must find orders by customer, date or total, never by reference.

**Seeding runs (2026-11-26).** Each failed attempt was discarded with a clean reinstall, so the final shop comes from one uninterrupted run:

1. Stopped at the first order: cart code needs PrestaShop's Symfony kernel, which a command-line script must start itself. Fixed by booting the admin kernel exactly as the back office does.
2. Stopped at the saved cart: the size/colour lookup returned only the size. Fixed by reading each combination's full attribute list.
3. Completed, but the Express order had $0 shipping: creating a price range auto-adds a $0 shop-level price that overrode the real one. Fixed by replacing prices the way the back office's carrier wizard does. (The order total was correct for what PrestaShop had been told; the seed had set the prices up wrongly.)
4. Completed, but the saved cart was not restored on login: PrestaShop only restores carts linked to a visitor ("guest") record, which storefront carts always have. Fixed by linking seeded carts to the customer's guest record.
5. Final run: completed with all checks passing.

**Verification** (`apps/prestashop/verify.sh` runs `seed/verify.php`, which builds throwaway carts through PrestaShop's own cart code, then resets the shop). All 22 checks passed on the final seed:

| Scenario | Expected and measured |
| --- | --- |
| 1 mug ($11.90), Standard | Shipping $5.00; total $17.85 |
| 4 mugs: $47.60 before tax, $51.41 after | Shipping $0.00: the free-shipping threshold uses the total after tax |
| 5 mugs ($59.50) | Shipping $0.00 |
| 2 posters ($58.00), Express | Shipping $15.00; total $77.64 |
| Same cart + `BENCH10` | Discount $6.26; total $71.38 |
| Same cart + `SAVE5` | Total $72.64 |
| `SAVE5` on $23.80 | Refused: "The minimum amount to benefit from this promo code is $30.00." |
| `EXPIRED` | Refused: "This voucher has expired" |
| Wes, 1 poster ($29.00), Standard | Products $24.65 (15% off); total $31.62 |
| Out-of-stock mug | Refused |
| 4 of the mug with 3 left | Refused |
| 1 of the minimum-2 mug / 2 of it | Refused / accepted |

Seeded orders, checked by hand: Casey Delivered $56.72 (sweater at $28.72 + 2 mugs = $52.52, free shipping, + 8%); Casey Shipped $46.32 ($29.00 + 8% + $15.00 Express); Wes $82.07 ($89.40 − 15% = $75.99, free shipping, + 8%).

**Logins (via the storefront and back-office login forms):** Casey and Wes reach "My account"; the demo customer is refused; Sam Sales lands on the Orders page. **Saved cart:** after Casey logs in, the cart shows 2 × t-shirt ($38.24) and 1 × mug ($11.90): subtotal $50.14, tax $4.01, shipping $0.00, total $54.15.

#### Reset

| Script | What it does |
| --- | --- |
| `apps/prestashop/snapshot.sh` | Run once after seeding. Saves a full database dump to `apps/prestashop/snapshot/` (gitignored, like BookStack's). The seed changes no files, so the database is the whole state. |
| `apps/prestashop/reset.sh` | Drops and restores the database, then clears PrestaShop's cache. Run before every test. Every test also starts with a fresh browser. |

Snapshot taken 2026-11-26: 1.0 MB of SQL. **Reset test:** after the verification carts and a test login, reset returned the shop to the snapshot exactly (9 carts, 8 orders, 4 customers, 4 guest records) in 5–6 seconds.

**Cloud and marketing modules disabled.** The demo install activates seven modules that connect to outside services. `seed.sh` disables them with PrestaShop's own module manager (`bin/console prestashop:module disable`); they stay installed and can be re-enabled with `enable`. The list is in `seed/structure.json`.

| Module | What it does |
| --- | --- |
| `ps_accounts` | Links the shop to a PrestaShop cloud account |
| `ps_eventbus` | Syncs shop data to PrestaShop's cloud services |
| `ps_distributionapiclient` | Fetches module updates from PrestaShop's distribution API |
| `ps_mbo` | Marketplace content in the back office |
| `ps_checkout` | PayPal payments |
| `ps_facebook` | Facebook marketing |
| `psxmarketingwithgoogle` | Google marketing |

Why: an outside service that is slow or fails could hang a page or show an error banner, which a tool would rightly report as FAIL on the clean build and we would wrongly count as a false fail. It also keeps checkout to the built-in payment options and stops benchmark runs from calling outside servers.

Verified after a full rebuild: all seven inactive; storefront and back-office dashboard load with no errors; checkout offers Casey and Wes exactly bank wire, check and cash on delivery (no PayPal); all 22 cart checks pass. The snapshot was retaken after this rebuild (2026-11-26, 1.0 MB).

## 5. Seeded bugs

30 bugs, 15 per app. Each has exactly one test, which passes on the clean build and should fail with the bug switched on (WebTestPilot's one-bug-per-test design). The test text never mentions the bug. Files: `bugs/` (see `bugs/README.md`).

### 5.1 What the tool under test must do

For each run the tool receives one written test case in plain English, runs it in a real browser, and returns PASS or FAIL. It does not know whether it faces the clean build or a bug. It is not asked to hunt for bugs without a test, write tests, diagnose causes or fix anything; Kane CLI's `--bug-detection` and `kane-cli bugs` are not used.

| App state | Tool says | Counted as |
| --- | --- | --- |
| Clean | PASS | Correct |
| Clean | FAIL | False fail |
| Bug on | FAIL | Bug caught (plus a human check that the failure reason points at the bug) |
| Bug on | PASS | False pass |

### 5.2 How bugs are switched on

Only the apps' official customisation points are used; the apps' own code is never edited. Server-side bugs were chosen over JavaScript injection (WebTestPilot's method) because injected JavaScript only changes what is drawn: saved data, emails and later pages stay correct, which no real bug does.

| App | Mechanism | Clean build |
| --- | --- | --- |
| BookStack | A setting or data change through the REST API, or a theme `functions.php` using BookStack's documented theme events (`APP_THEME=bench`) | Theme folder `bench` is empty |
| PrestaShop | A setting change through PrestaShop's own classes, or an official override | No overrides |

`bugs/apply.sh <app> <clean|BUG-ID>` resets the app, switches the bug on, then runs the bug's `check.sh` to confirm it is active; if the check fails it resets again and exits with an error, so a run never starts on the wrong build. `clean` runs `check-clean.sh` instead. This guards against counting a correct PASS as a false pass because a bug silently failed to apply.

Setup change for this: BookStack's container now sets `APP_THEME=bench`, and its `reset.sh` empties the `bench` theme folder. On the clean build the folder is empty, so BookStack behaves as stock; `check-clean.sh` confirms the folder is empty, the Viewer cannot open Roadmap, and `rollback.sh` is attached to Rollback Procedure.

### 5.3 Bug types

| Type | BookStack | PrestaShop | Total |
| --- | --- | --- | --- |
| Wrong data or calculation | 2 | 6 | 8 |
| Shown to the wrong role | 3 | 2 | 5 |
| Validation not enforced | 2 | 3 | 5 |
| Missing element | 3 | 1 | 4 |
| Wrong navigation | 2 | 1 | 3 |
| Action does nothing | 2 | 1 | 3 |
| Silent failure (success shown, nothing saved) | 1 | 1 | 2 |
| **Total** | **15** | **15** | **30** |

The first six types come from WebTestPilot's analysis of real GitHub bug reports. Silent failure was added because it is the strongest false-pass trap: the page says it worked. Crashes are excluded: any tool catches them.

Severity: 5 critical, 3 major, 1 minor.

### 5.4 The 30 bugs

| ID | Type | Bug | Sev | Status |
| --- | --- | --- | --- | --- |
| BS-01 | Wrong role | Vera (Viewer) can see and open the Roadmap book | 5 | Built (pilot) |
| BS-02 | Wrong role | Editors can open HR Policies pages by URL, though the list hides them | 5 | Planned |
| BS-03 | Silent failure | Editing a page shows "Page successfully updated" but the change is lost | 5 | Built (pilot) |
| BS-04 | Wrong data | Rate Limits shows an old revision instead of the latest | 3 | Planned |
| BS-05 | Wrong navigation | Onboarding's "Getting Started" opens the API Guide's "Getting Started" | 3 | Built (pilot) |
| BS-06 | Wrong navigation | The v2.10 Release Notes link opens v2.1 | 3 | Planned |
| BS-07 | Missing element | Rollback Procedure's attachment `rollback.sh` is missing | 3 | Built (pilot) |
| BS-08 | Missing element | The architecture image on Production is broken | 1 | Planned |
| BS-09 | Wrong data | Searching "rate limit" does not return the Rate Limits page | 3 | Planned |
| BS-10 | Action does nothing | Restoring a page revision does nothing | 3 | Planned |
| BS-11 | Validation | A page can be saved with an empty title | 3 | Built (pilot) |
| BS-12 | Missing element | Page tags are not displayed | 1 | Planned |
| BS-13 | Action does nothing | Favouriting a page does nothing | 3 | Planned |
| BS-14 | Wrong role | Hana (HR) can edit the Deployment Runbook (should only view) | 5 | Planned |
| BS-15 | Validation | An admin can create a user with an invalid email address | 3 | Planned |
| PS-01 | Wrong calculation | Tax is 0.8% instead of 8% | 5 | Built (pilot) |
| PS-02 | Wrong calculation | BENCH10 is applied twice (20% off) | 5 | Planned |
| PS-03 | Wrong calculation | Order total is off by $0.01 (rounding) | 3 | Built (pilot) |
| PS-04 | Wrong calculation | Free shipping is not applied over $50 | 3 | Planned |
| PS-05 | Validation | The EXPIRED voucher is accepted | 5 | Built (pilot) |
| PS-06 | Validation | The out-of-stock mug can be ordered | 3 | Planned |
| PS-07 | Validation | The minimum-2 mug can be bought singly | 3 | Planned |
| PS-08 | Wrong role | Casey (regular customer) gets the Wholesale 15% discount | 5 | Built (pilot) |
| PS-09 | Wrong role | Sam Sales can change product prices in the back office | 5 | Planned |
| PS-10 | Wrong data | Casey's order history shows another customer's order | 5 | Planned |
| PS-11 | Action does nothing | The cart's remove button does nothing | 3 | Built (pilot) |
| PS-12 | Silent failure | Casey's saved cart is not restored after login | 3 | Planned |
| PS-13 | Wrong navigation | "Brown bear cushion" in the product list opens "Mountain fox cushion" | 3 | Planned |
| PS-14 | Missing element | The Express shipping option is missing at checkout | 3 | Planned |
| PS-15 | Wrong data | Wes's order shows "Delivered" although it awaits payment | 3 | Planned |

Full run: 30 tests × 2 builds = 60 expected verdicts per tool per repeat.

### 5.5 Pilot bugs built

Each was tested in three states: clean (control), bug on, clean again.

#### BookStack

Applying any state, including the reset, takes 2–4 seconds.

| ID | How it is switched on | Check before every run | Verified behaviour |
| --- | --- | --- | --- |
| BS-01 | API: Roadmap permission for Viewer set to view | Vera opens `/books/roadmap`: 200 (clean: 404) | Vera sees and opens Roadmap |
| BS-03 | Theme: page updates answered with the success notice and a redirect, without saving | Theme file present; a real update leaves the page name unchanged | "Page successfully updated" shown; name still "Glossary". Control on clean: the same update renamed the page |
| BS-05 | Theme: Onboarding's Getting Started redirected to the API Guide's | Theme file present; redirect target is the API Guide page | Page shows the API base URL instead of "IT desk on floor 3" |
| BS-07 | API: attachment moved to Incident Response | The Rollback Procedure page has no `rollback.sh` attachment entry (the page text still mentions the file, by design) | 0 attachment entries on Rollback Procedure, 1 on Incident Response; clean: 1 on Rollback Procedure |
| BS-11 | Theme: empty title replaced by an invisible character before validation | Theme file present (a save-based check would create a page right before the test) | Verified once on 2026-11-26: clean refuses the save and returns to the editor; with the bug the page is saved at `/page/04511` with a blank title |

#### PrestaShop

Applying any state, including the reset, takes 4–9 seconds.

`reset.sh` now also deletes everything in `override/` except its `index.php`, then clears the cache, which holds PrestaShop's list of active overrides. Checks use `bugs/prestashop/probe.php`, which reads the shop facts the bugs change (tax rate, Casey's group, a 1-mug cart's totals, whether `EXPIRED` is accepted). Its carts are deleted straight after measuring: otherwise PrestaShop would restore the probe's cart instead of Casey's saved cart when a test logs her in. Verified: Casey's most recent cart is still her saved cart after a probe.

`check-clean.sh` confirms: no override files; tax rate 8; Casey's default group is Customer; the 1-mug cart is products $11.90, tax $0.95, shipping $5.00, total $17.85; `EXPIRED` is refused.

| ID | How it is switched on | Check before every run | Verified behaviour (storefront, with clean control) |
| --- | --- | --- | --- |
| PS-01 | Setting: "US Sales Tax" rate 8 → 0.8 (Tax class) | Probe: rate 0.8; 1-mug cart tax $0.10, total $17.00 | Cart computes 0.8% tax |
| PS-03 | Override `classes/Cart.php`: grand total with tax + $0.01 | Override file present; 1-mug cart parts unchanged, total $17.86 | Casey's cart page shows $54.16 (clean $54.15) |
| PS-05 | Override `classes/CartRule.php`: end-date check skipped | Override file present; probe finds `EXPIRED` accepted | Entering `EXPIRED` on the cart page is accepted and the total drops (clean: "This voucher has expired", total unchanged) |
| PS-08 | Data: Casey added to Wholesale, made her default group (Customer class) | Probe: default group Wholesale; 1-mug cart products $10.12 | Prices 15% lower for Casey |
| PS-11 | Override `controllers/front/CartController.php`: remove does nothing | Override file present (a remove-based check would change Casey's saved cart before the test) | Clicking remove on Casey's mug leaves it in the cart at $54.15 (clean: the mug is removed) |

Two details found while building:

- **PrestaShop archives a tax when its rate changes.** Because past orders used "US Sales Tax", changing its rate creates a new tax with the same name and marks the old one deleted, keeping order history intact. Scripts therefore look up only the current (not deleted) tax.
- **PS-03 reaches the placed order too:** the override changes the grand total everywhere PrestaShop computes it, so cart, checkout and order agree on the wrong amount, as a real bug would.

### 5.6 Held-out variants

About a quarter of the bugs (8 of 30) will get a private variant: same type and same test, different details (for example tax 7.2% instead of 0.8%, or a different lookalike pair). They stop tools, and AI models trained on public code, from being tuned to the public bug list. Published results report public and held-out bugs separately; a tool that does much better on the public bugs has likely been tuned to them. After each edition the variants are published and new ones made. Storage: to be decided (private repository or a local folder that is never committed).

### 5.7 Pilot

The pilot uses 10 bugs (BS-01, 03, 05, 07, 11; PS-01, 03, 05, 08, 11) to check costs, time and the harness. **Pilot results are for analysis only; the published metrics come from the full 30-bug run.**

## 6. Tests

One test per bug, in `tests/<app>/T-<APP>-<NN>.md` (format and writing rules: `tests/README.md`). Only the text under `## Test` is given to the tools, word for word. Passwords appear as `{{password}}` and are filled in by the harness from each app's `.env`.

Every value a test asserts was measured on the clean build through the app's own pages on 2026-11-26, using the app's on-screen wording. Where the right-reason note states a value seen with the bug, it was measured with the bug on.

### 6.1 Pilot tests (10)

| Test | User | What it checks | Clean build (measured) | With its bug (measured) |
| --- | --- | --- | --- | --- |
| T-BS-01 | Vera (Viewer) | The Books page lists exactly 4 books | API Guide, Deployment Runbook, Onboarding, Release Notes | Roadmap also listed |
| T-BS-03 | Erin (Editor) | Renaming Glossary to "Glossary of Terms" is saved | Title changes | "Page successfully updated" shown; title stays "Glossary" |
| T-BS-05 | Vera | Onboarding › First Week › Getting Started shows the first-day checklist | "IT desk on floor 3", buddy at 11:00 | API Guide's page (API base URL) |
| T-BS-07 | Vera | Rollback Procedure has the attachment `rollback.sh` | Listed | Not listed (attached to Incident Response) |
| T-BS-11 | Erin | Saving a new page with an empty title is refused | Editor stays open: "The name field is required." | Page saved with a blank title |
| T-PS-01 | Wes (Wholesale) | Cart with 1 poster | 1 item $24.65; Shipping $5.00; Total (tax excl.) $29.65; Taxes $1.97; Total (tax incl.) $31.62 | Taxes $0.20; Total (tax incl.) $29.85 |
| T-PS-03 | Casey | Saved cart contents and summary | 3 items $50.14; Shipping Free; Total (tax excl.) $50.14; Taxes $4.01; Total (tax incl.) $54.15 | Total (tax incl.) $54.16 |
| T-PS-05 | Casey | Promo code `EXPIRED` is refused | "This voucher has expired"; total $54.15 | Accepted; total drops |
| T-PS-08 | Casey | Poster price on its product page (40x60cm) | "Price: $29.00" | "Price: $24.65" |
| T-PS-11 | Casey | Removing the mug from the cart | 2 items $38.24; Shipping $5.00; Total (tax excl.) $43.24; Taxes $3.06; Total (tax incl.) $46.30 | Mug still in the cart; $54.15 |

Notes from measuring:

- Removing the mug drops Casey's cart below the $50 free-shipping threshold (after tax), so Standard shipping becomes $5.00. T-PS-11 asserts this, which also exercises the threshold.
- With PS-08 on, every product is 15% cheaper for Casey (her t-shirts show $16.25), so other Casey tests would fail too. That is expected: each bug is only ever run with its own test.
- Two measurement mistakes of mine were caught and redone: a probe that re-sent a form after a redirect (BookStack answered "Page Expired"), and one that read the header mini-cart's prices instead of the product's own price.

## 7. Harness and run settings

Not yet built.

## 8. Results

No runs yet.

## 9. Work log

Newest first.

| Date | Step | What was done |
| --- | --- | --- |
| 2026-11-26 | Pilot tests | Measured every asserted value on the clean build (and bug-side values for the review notes); wrote `tests/README.md` and the 10 pilot tests |
| 2026-11-26 | Bugs: PrestaShop pilot | Wrote `bugs/lib/prestashop.sh`, `bugs/lib/ps-bootstrap.php`, `bugs/prestashop/probe.php`, `check-clean.sh`; reset now removes overrides; built and verified PS-01, 03, 05, 08, 11, including storefront checks with clean controls |
| 2026-11-26 | Bugs: framework + BookStack pilot | Wrote `bugs/apply.sh`, `bugs/lib/bookstack.sh`, `check-clean.sh`; set `APP_THEME=bench` and made reset empty the theme folder; built and verified BS-01, 03, 05, 07, 11 with clean controls |
| 2026-11-26 | PrestaShop modules | Added module disabling to `seed.sh`; full rebuild from scratch (install, post-install, seed, snapshot) succeeded; verified modules off, pages load, payment options, all 22 cart checks |
| 2026-11-26 | PrestaShop seed, verify, reset | Seeded after four fixes, each followed by a clean reinstall; verified 22 cart checks to the cent, logins and the saved cart; wrote `verify.sh`, `snapshot.sh`, `reset.sh`; took the snapshot; reset tested |
| 2026-11-26 | PrestaShop seed (prepared) | Wrote `seed/structure.json`, `seed/seed.php` and `seed.sh`; syntax-checked with the container's PHP |
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
