"""Seed BookStack with the benchmark's fixed content through the REST API.

Reads structure.json (what to create) and content/<book>/<page>.md (page bodies).
Needs an empty BookStack: it refuses to run if any book already exists.

Usage (from apps/bookstack):  python3 seed/seed.py
Credentials come from apps/bookstack/.env: API_TOKEN_ID, API_TOKEN_SECRET, USER_PASSWORD.

Placeholders in page Markdown:
  asset:<file>        an image from seed/assets/, uploaded to the gallery first
  page:<book>/<page>  a link to another seeded page (book key / page key)
"""
import json
import mimetypes
import re
import sys
import time
import urllib.error
import urllib.request
import uuid
from pathlib import Path

SEED = Path(__file__).resolve().parent
APP = SEED.parent
BASE = "http://localhost:6875"


def load_env():
    env = {}
    for line in (APP / ".env").read_text().splitlines():
        if "=" in line and not line.lstrip().startswith("#"):
            k, v = line.split("=", 1)
            env[k.strip()] = v.strip()
    for key in ("API_TOKEN_ID", "API_TOKEN_SECRET", "USER_PASSWORD"):
        if not env.get(key):
            sys.exit(f"Missing {key} in apps/bookstack/.env")
    return env


ENV = load_env()
AUTH = f"Token {ENV['API_TOKEN_ID']}:{ENV['API_TOKEN_SECRET']}"


def call(method, path, body=None, files=None):
    """JSON request, or multipart when files are given. Retries on HTTP 429."""
    headers = {"Authorization": AUTH, "Accept": "application/json"}
    if files:
        boundary = uuid.uuid4().hex
        parts = []
        for k, v in (body or {}).items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
        for k, p in files.items():
            mime = mimetypes.guess_type(p.name)[0] or "application/octet-stream"
            parts.append(
                f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{p.name}"\r\n'
                f"Content-Type: {mime}\r\n\r\n".encode() + p.read_bytes() + b"\r\n"
            )
        data = b"".join(parts) + f"--{boundary}--\r\n".encode()
        headers["Content-Type"] = f"multipart/form-data; boundary={boundary}"
    else:
        data = json.dumps(body).encode() if body is not None else None
        headers["Content-Type"] = "application/json"
    for attempt in range(5):
        req = urllib.request.Request(BASE + path, data=data, headers=headers, method=method)
        try:
            with urllib.request.urlopen(req) as r:
                raw = r.read()
                return json.loads(raw) if raw else {}
        except urllib.error.HTTPError as e:
            if e.code == 429 and attempt < 4:
                time.sleep(int(e.headers.get("Retry-After", "10")))
                continue
            sys.exit(f"{method} {path} failed: HTTP {e.code} {e.read().decode()[:500]}")


def slug(name):
    """BookStack's slug rule for plain ASCII names (verified against the API after creation)."""
    s = re.sub(r"[^a-z0-9\s-]", "", name.lower())
    return re.sub(r"[\s-]+", "-", s).strip("-")


def tags(pairs):
    return [{"name": n, "value": v} for n, v in pairs]


def main():
    spec = json.loads((SEED / "structure.json").read_text())

    if call("GET", "/api/books")["total"] > 0:
        sys.exit("BookStack already has books. Seed only runs on an empty instance; reset first.")

    # Page URLs are predictable from book and page names, so links can point forward.
    book_names = {b["key"]: b["name"] for b in spec["books"]}
    url_of = {}
    for b in spec["books"]:
        for item in b["contents"]:
            for p in item["pages"] if "chapter" in item else [item["page"]]:
                url_of[f"{b['key']}/{p['key']}"] = f"{BASE}/books/{slug(b['name'])}/page/{slug(p['title'])}"

    # Roles: built-in roles by display name, plus the custom ones.
    role_id = {r["display_name"]: r["id"] for r in call("GET", "/api/roles")["data"]}
    for r in spec["roles"]:
        made = call("POST", "/api/roles", {"display_name": r["display_name"], "description": r["description"],
                                           "permissions": r["permissions"]})
        role_id[r["key"]] = made["id"]
        print(f"role    {r['display_name']} (id {made['id']})")

    for u in spec["users"]:
        made = call("POST", "/api/users", {
            "name": u["name"], "email": u["email"], "password": ENV["USER_PASSWORD"],
            "roles": [role_id[x] if x in role_id else role_id[x.lower()] for x in u["roles"]],
            "send_invite": False,
        })
        print(f"user    {u['name']} <{u['email']}> (id {made['id']})")

    image_url = {}
    first_page_id = None

    def render(book_key, text):
        def asset(m):
            name = m.group(1)
            if name not in image_url:  # uploaded once, attached to the first seeded page
                up = call("POST", "/api/image-gallery",
                          {"type": "gallery", "uploaded_to": first_page_id, "name": name},
                          files={"image": SEED / "assets" / name})
                image_url[name] = up["url"]
            return image_url[name]
        text = re.sub(r"asset:([\w.-]+)", asset, text)
        return re.sub(r"page:([\w-]+/[\w-]+)", lambda m: url_of[m.group(1)], text)

    def make_page(book, parent, p):
        nonlocal first_page_id
        folder = SEED / "content" / book["key"]
        versions = [folder / v for v in p.get("revisions", [])] + [folder / f"{p['key']}.md"]
        made = call("POST", "/api/pages", {**parent, "name": p["title"], "tags": tags(p["tags"]),
                                           "markdown": render(book["key"], versions[0].read_text())})
        first_page_id = first_page_id or made["id"]
        for v in versions[1:]:  # each update becomes a page revision
            call("PUT", f"/api/pages/{made['id']}", {"markdown": render(book["key"], v.read_text())})
        for a in p.get("attachments", []):
            call("POST", "/api/attachments", {"name": a, "uploaded_to": made["id"]},
                 files={"file": SEED / "assets" / a})
        expected = slug(p["title"])
        if made["slug"] != expected:
            sys.exit(f"Slug mismatch for {p['title']}: got {made['slug']}, links assume {expected}")
        extra = f", {len(versions) - 1} revisions" if len(versions) > 1 else ""
        print(f"  page  {p['title']} (id {made['id']}{extra})")

    book_id = {}
    for b in spec["books"]:
        made = call("POST", "/api/books", {"name": b["name"], "description": b["description"], "tags": tags(b["tags"])})
        book_id[b["key"]] = made["id"]
        if made["slug"] != slug(b["name"]):
            sys.exit(f"Slug mismatch for book {b['name']}: got {made['slug']}")
        print(f"book    {b['name']} (id {made['id']})")
        for item in b["contents"]:
            if "chapter" in item:
                ch = call("POST", "/api/chapters", {"book_id": made["id"], "name": item["chapter"],
                                                     "description": item["description"]})
                print(f"chapter {item['chapter']}")
                for p in item["pages"]:
                    make_page(b, {"chapter_id": ch["id"]}, p)
            else:
                make_page(b, {"book_id": made["id"]}, item["page"])
        if "permissions" in b:
            perms = b["permissions"]
            call("PUT", f"/api/content-permissions/book/{made['id']}", {
                "fallback_permissions": perms["fallback"],
                "role_permissions": [{"role_id": role_id[k], **v} for k, v in perms["roles"].items()],
            })
            print(f"  permissions set on {b['name']}")

    for s in spec["shelves"]:
        call("POST", "/api/shelves", {"name": s["name"], "description": s["description"],
                                      "books": [book_id[k] for k in s["books"]]})
        print(f"shelf   {s['name']} ({', '.join(book_names[k] for k in s['books'])})")

    print("Seed complete.")


if __name__ == "__main__":
    main()
