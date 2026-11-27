#!/usr/bin/env python3
"""Benchmark runner: runs tests on clean and bugged builds with Kane CLI and Claude Code.

Usage (from the repo root):
  python3 harness/run.py --label smoke --tests T-PS-03 --repeats 1
  python3 harness/run.py --label check --tests all --repeats 1
  python3 harness/run.py ... --dry-run        # show the plan and commands, run nothing

For every run (test x build x tool x repeat), in a shuffled order with a fixed seed:
  1. bugs/apply.sh <app> clean|<bug>  resets the app and confirms its state
  2. the tool runs the test from a new, empty folder outside the repo
  3. one JSON line goes to results/<timestamp>-<label>/runs.jsonl

Python standard library only.
"""
import argparse
import datetime as dt
import json
import os
import random
import re
import shutil
import subprocess
import sys
import tempfile
import time
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
HARNESS = REPO / "harness"

# Frozen versions (BENCHMARKING-REPORT.md, section 2). The runner refuses to start if any differ.
FROZEN = {"kane-cli": "0.10.11", "claude": "2.1.332", "playwright-mcp": "0.0.86"}
CLAUDE_MODEL = "claude-opus-5-5"

MAX_STEPS = 50          # Kane CLI steps / Claude Code turns
RUN_TIMEOUT = 600       # seconds per run, both tools
KILL_GRACE = 60         # extra seconds before the harness kills a run itself
WINDOW = "1920x1080"
PLAYWRIGHT_CLI = HARNESS / "node_modules" / "@playwright" / "mcp" / "cli.js"
SECRET_NAME = "BENCH_PASSWORD"


# ---------- tests ----------

def load_tests():
    tests = {}
    for path in sorted((REPO / "tests").glob("*/T-*.md")):
        text = path.read_text()
        m = re.match(r"---\n(.*?)\n---\n(.*)", text, re.S)
        if not m:
            sys.exit(f"{path}: no front matter")
        front, body = m.groups()
        meta = {}
        for line in front.splitlines():
            km = re.match(r"([a-z_]+):\s*(.*?)\s*(#.*)?$", line)
            if km and not line.startswith(" "):
                meta[km.group(1)] = km.group(2)
        tm = re.search(r"^## Test\s*\n(.*)$", body, re.S | re.M)
        if not tm:
            sys.exit(f"{path}: no '## Test' section")
        meta["text"] = tm.group(1).strip()
        meta["file"] = str(path.relative_to(REPO))
        tests[meta["id"]] = meta
    return tests


def app_password(app):
    for line in (REPO / "apps" / app / ".env").read_text().splitlines():
        if line.startswith("USER_PASSWORD="):
            return line.split("=", 1)[1].strip().strip('"').strip("'")
    sys.exit(f"apps/{app}/.env has no USER_PASSWORD")


# ---------- versions ----------

def versions():
    def out(cmd):
        try:
            return subprocess.run(cmd, capture_output=True, text=True, timeout=60).stdout.strip()
        except Exception as e:  # noqa: BLE001
            return f"error: {e}"
    pkg = json.loads((PLAYWRIGHT_CLI.parent / "package.json").read_text())
    return {
        "kane-cli": (out(["kane-cli", "--version"]).splitlines() or [""])[-1],
        "claude": out(["claude", "--version"]).split()[0],
        "playwright-mcp": pkg["version"],
        "chrome": out(["/Applications/Google Chrome.app/Contents/MacOS/Google Chrome", "--version"]),
        "claude_model": CLAUDE_MODEL,
    }


def kane_balance():
    out = subprocess.run(["kane-cli", "balance"], capture_output=True, text=True, timeout=60).stdout
    m = re.search(r"Available credits:\s*([0-9.]+)", out)
    return float(m.group(1)) if m else None


# ---------- tools ----------

def kane_cmd(test, run_dir, work, password):
    varfile = work / "variables.json"
    varfile.write_text(json.dumps({"password": {"value": password, "secret": True}}))
    varfile.chmod(0o600)
    return [
        "kane-cli", "run", test["text"],
        "--url", test["start_url"],
        "--agent", "--headless",
        "--max-steps", str(MAX_STEPS),
        "--timeout", str(RUN_TIMEOUT),
        "--mode", "testing",
        "--assertion-mode", "dom",
        "--final-validation", "off",
        "--bug-detection", "off",
        "--variables-file", str(varfile),
        "--chrome-profile", str(work / "chrome-profile"),
        "--name", run_dir.name,
    ]


def claude_cmd(test, run_dir, work, password):
    secrets = work / "secrets.env"
    if "'" in password or "\n" in password:
        sys.exit("password contains a quote or newline; cannot write the secrets file safely")
    secrets.write_text(f"{SECRET_NAME}='{password}'\n")
    secrets.chmod(0o600)
    mcp = {"mcpServers": {"playwright": {"command": "node", "args": [
        str(PLAYWRIGHT_CLI),
        "--browser", "chrome",
        "--headless",
        "--isolated",
        "--viewport-size", WINDOW,
        "--secrets", str(secrets),
        "--output-dir", str(run_dir / "playwright"),
    ]}}}
    (work / "mcp.json").write_text(json.dumps(mcp))
    prompt = (HARNESS / "claude-instructions.md").read_text() \
        .replace("{start_url}", test["start_url"]).replace("{test_text}", test["text"])
    (run_dir / "prompt.txt").write_text(prompt)
    return [
        "claude", "-p", prompt,
        "--model", CLAUDE_MODEL,
        "--output-format", "stream-json", "--verbose",
        "--max-turns", str(MAX_STEPS),
        "--tools", "",                      # no built-in tools: no files, shell or web
        "--mcp-config", str(work / "mcp.json"),
        "--strict-mcp-config",              # only Playwright MCP; no other servers or connectors
        "--allowedTools", "mcp__playwright__*",
        "--permission-mode", "dontAsk",     # anything not allowed is refused, never prompted
        "--setting-sources", "project",     # skip user settings, hooks and plugins (the folder is empty)
        "--disable-slash-commands",         # no skills
        "--no-session-persistence",
    ]


def read_ndjson(path):
    events = []
    for line in path.read_text(errors="replace").splitlines():
        line = line.strip()
        if line.startswith("{"):
            try:
                events.append(json.loads(line))
            except json.JSONDecodeError:
                pass
    return events


def parse_kane(exit_code, run_dir):
    events = read_ndjson(run_dir / "stdout.ndjson")
    end = next((e for e in reversed(events) if e.get("type") == "run_end"), {})
    blob = json.dumps(end)
    credits = re.findall(r'"credits_consumed":\s*([0-9.]+)', blob)
    reason = None
    for key in ("summary", "reason", "final_summary", "message"):
        m = re.search(rf'"{key}":\s*"((?:[^"\\]|\\.)*)"', blob)
        if m:
            reason = json.loads(f'"{m.group(1)}"')
            break
    verdict = {0: "PASS", 1: "FAIL", 2: "ERROR", 3: "TIMEOUT"}.get(exit_code, "ERROR")
    return {
        "verdict": verdict,
        "steps": sum(1 for e in events if e.get("type") == "step_end"),
        "credits": float(credits[0]) if credits else None,
        "reason": reason,
    }


def parse_claude(exit_code, run_dir):
    events = read_ndjson(run_dir / "stdout.ndjson")
    init = next((e for e in events if e.get("type") == "system" and e.get("subtype") == "init"), {})
    result = next((e for e in reversed(events) if e.get("type") == "result"), {})
    text = result.get("result") or ""
    m = re.findall(r"VERDICT:\s*(PASS|FAIL)\b", text)
    verdict = m[-1] if m else ("TIMEOUT" if exit_code == "timeout" else "ERROR")
    lines = [ln.strip() for ln in text.strip().splitlines() if ln.strip()]
    reason = lines[-2] if len(lines) >= 2 else (lines[0] if lines else None)
    # Fairness check: the session must have had Playwright tools only.
    tools = init.get("tools") or []
    extra = [t for t in tools if not t.startswith("mcp__playwright__")]
    usage = result.get("usage") or {}
    return {
        "verdict": verdict,
        "steps": result.get("num_turns"),
        "tool_calls": sum(1 for e in events if e.get("type") == "assistant"
                          for c in (e.get("message", {}).get("content") or [])
                          if isinstance(c, dict) and c.get("type") == "tool_use"),
        "tokens_in": (usage.get("input_tokens") or 0) + (usage.get("cache_creation_input_tokens") or 0)
                     + (usage.get("cache_read_input_tokens") or 0),
        "tokens_out": usage.get("output_tokens"),
        "cost_usd_equivalent": result.get("total_cost_usd"),
        "model": next(iter(result.get("modelUsage") or {}), init.get("model")),
        "tools_available": len(tools),
        "isolation_ok": bool(tools) and not extra,
        "isolation_extra": extra,
        "reason": reason,
    }


# ---------- one run ----------

def run_one(run, tests, out_dir, dry):
    test = tests[run["test"]]
    app = test["app"]
    state = "clean" if run["build"] == "clean" else test["bug"]
    run_dir = out_dir / "runs" / run["id"]
    row = {**run, "app": app, "bug": test["bug"], "state": state,
           "expected": "PASS" if run["build"] == "clean" else "FAIL",
           "started": dt.datetime.now().isoformat(timespec="seconds")}

    if dry:
        return row

    run_dir.mkdir(parents=True)
    t0 = time.time()
    setup = subprocess.run([str(REPO / "bugs" / "apply.sh"), app, state],
                           capture_output=True, text=True, cwd=REPO)
    (run_dir / "setup.txt").write_text(setup.stdout + setup.stderr)
    row["setup_seconds"] = round(time.time() - t0, 1)
    if setup.returncode != 0:
        row.update(verdict="SETUP_ERROR", correct=None)
        return row

    work = Path(tempfile.mkdtemp(prefix="bench-run-"))   # empty folder outside the repo
    password = app_password(app)
    try:
        cmd = (kane_cmd if run["tool"] == "kane" else claude_cmd)(test, run_dir, work, password)
        t0 = time.time()
        with open(run_dir / "stdout.ndjson", "w") as so, open(run_dir / "stderr.txt", "w") as se:
            proc = subprocess.Popen(cmd, cwd=work, stdout=so, stderr=se, text=True,
                                    stdin=subprocess.DEVNULL, start_new_session=True)
            try:
                exit_code = proc.wait(timeout=RUN_TIMEOUT + KILL_GRACE)
            except subprocess.TimeoutExpired:
                os.killpg(proc.pid, 9)
                proc.wait()
                exit_code = "timeout"
        row["seconds"] = round(time.time() - t0, 1)
        row["exit_code"] = exit_code
        if exit_code == "timeout" and run["tool"] == "kane":
            exit_code = 3
        row.update((parse_kane if run["tool"] == "kane" else parse_claude)(exit_code, run_dir))
    finally:
        shutil.rmtree(work, ignore_errors=True)            # removes the password files and the profile

    if row.get("isolation_ok") is False:
        row["verdict"] = "ISOLATION_ERROR"
    row["correct"] = (row["verdict"] == row["expected"]) if row["verdict"] in ("PASS", "FAIL") else None
    return row


# ---------- main ----------

def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--label", required=True, help="short name for this batch, e.g. smoke or check")
    ap.add_argument("--tests", default="all", help="comma-separated test ids, or 'all'")
    ap.add_argument("--builds", default="clean,bug")
    ap.add_argument("--tools", default="kane,claude")
    ap.add_argument("--repeats", type=int, default=1)
    ap.add_argument("--seed", type=int, default=20261127)
    ap.add_argument("--dry-run", action="store_true", help="print the plan and commands; run nothing")
    args = ap.parse_args()

    tests = load_tests()
    ids = sorted(tests) if args.tests == "all" else args.tests.split(",")
    for t in ids:
        if t not in tests:
            sys.exit(f"unknown test {t}")
    builds, tools = args.builds.split(","), args.tools.split(",")

    runs = [{"test": t, "build": b, "tool": tl, "repeat": r}
            for t in ids for b in builds for tl in tools for r in range(1, args.repeats + 1)]
    random.Random(args.seed).shuffle(runs)
    for i, r in enumerate(runs, 1):
        r["order"] = i
        r["id"] = f"{i:03d}-{r['test']}-{r['build']}-{r['tool']}-r{r['repeat']}"

    vers = versions()
    bad = {k: vers[k] for k in FROZEN if not vers[k].startswith(FROZEN[k])}
    stamp = dt.datetime.now().strftime("%Y%m%d-%H%M%S")
    out_dir = REPO / "results" / f"{stamp}-{args.label}"

    print(f"{len(runs)} runs, seed {args.seed}, versions {vers}")
    if bad:
        print(f"Versions differ from the frozen set: {bad} (expected {FROZEN})")
        if not args.dry_run:
            sys.exit(1)

    if args.dry_run:
        for r in runs:
            print(f"{r['order']:3d}. {r['test']:8s} {r['build']:5s} {r['tool']:6s} repeat {r['repeat']}")
        sample = tests[ids[0]]
        for name, fn in (("Kane CLI", kane_cmd), ("Claude Code", claude_cmd)):
            print(f"\n{name} command ({sample['id']}):")
            print(" ".join(f"'{a}'" if (" " in a or "\n" in a or a == "" or "*" in a) else a
                           for a in _preview(fn, sample)))
        return

    out_dir.mkdir(parents=True)
    plan = {"label": args.label, "seed": args.seed, "versions": vers, "max_steps": MAX_STEPS,
            "timeout_s": RUN_TIMEOUT, "window": WINDOW, "runs": runs,
            "kane_balance_before": kane_balance() if "kane" in tools else None}
    (out_dir / "plan.json").write_text(json.dumps(plan, indent=2))

    with open(out_dir / "runs.jsonl", "a") as f:
        for r in runs:
            row = run_one(r, tests, out_dir, False)
            f.write(json.dumps(row) + "\n")
            f.flush()
            print(f"{r['order']:3d}/{len(runs)} {r['id']}: {row.get('verdict')} "
                  f"(expected {row['expected']}, {row.get('seconds')} s, steps {row.get('steps')}, "
                  f"credits {row.get('credits')}, tokens out {row.get('tokens_out')})", flush=True)

    plan["kane_balance_after"] = kane_balance() if "kane" in tools else None
    plan["finished"] = dt.datetime.now().isoformat(timespec="seconds")
    (out_dir / "plan.json").write_text(json.dumps(plan, indent=2))
    print(f"Done. Results in {out_dir.relative_to(REPO)}. Kane balance {plan['kane_balance_before']} -> "
          f"{plan['kane_balance_after']}")


def _preview(fn, test):
    """Build a command in a throwaway folder with a dummy password, so nothing real is printed."""
    tmp = Path(tempfile.mkdtemp(prefix="bench-preview-"))
    try:
        rd = tmp / "run"
        rd.mkdir()
        cmd = fn(test, rd, tmp, "********")
        return ["<test text>" if a == test["text"] else
                "<instructions + test text>" if a.startswith("You are a QA tester") else
                a.replace(str(tmp), "<temp>") for a in cmd]
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    main()
