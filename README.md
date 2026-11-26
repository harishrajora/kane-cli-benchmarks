# Kane CLI Benchmarks

An open, reproducible benchmark of testing-agent verdict accuracy. Each test runs against two builds of the same app: a clean build, where the correct verdict is PASS, and a bug-seeded build, where the correct verdict is FAIL. A tool's wrong verdicts are counted as false passes and false fails. Speed, steps, consistency across repeats and cost are measured alongside.

Tools compared: Kane CLI and Claude Code with Playwright MCP. Apps under test: BookStack (wiki and documentation) and PrestaShop (e-commerce), both self-hosted in Docker at pinned versions. Grading is deterministic: no AI judge is used.

## Layout

| Folder | Contents |
| --- | --- |
| `apps/` | Docker Compose setup, seed data and reset script for each app |
| `bugs/` | The seeded-bug list: ID, type, severity, affected test, how it is switched on |
| `tests/` | Test cases, written once and given to every tool |
| `harness/` | Scripts that run the tools and record results |
| `results/` | Run outputs (not committed) |
| `ENVIRONMENT.md` | Tool versions, settings and the machine the benchmark runs on |
