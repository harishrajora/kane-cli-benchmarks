"""Generate the seed images for BookStack. Output is committed; rerun only to change them.

Usage: python3 make_assets.py
"""
from pathlib import Path

from PIL import Image, ImageDraw

OUT = Path(__file__).resolve().parent / "assets"
INK, LINE, FILL = (30, 30, 30), (120, 120, 120), (232, 240, 252)


def boxes_diagram(name, title, boxes, arrows):
    img = Image.new("RGB", (800, 360), "white")
    d = ImageDraw.Draw(img)
    d.text((24, 20), title, fill=INK)
    for a, b in arrows:
        (ax, ay), (bx, by) = boxes[a], boxes[b]
        if ay == by:  # same row: right edge of a to left edge of b
            d.line([(ax + 180, ay + 30), (bx, by + 30)], fill=LINE, width=2)
        else:  # different rows: bottom of a to top of b
            d.line([(ax + 90, ay + 60), (bx + 90, by)], fill=LINE, width=2)
    for label, (x, y) in boxes.items():
        d.rectangle([x, y, x + 180, y + 60], outline=LINE, fill=FILL, width=2)
        d.text((x + 14, y + 24), label, fill=INK)
    img.save(OUT / name)


def main():
    OUT.mkdir(exist_ok=True)
    boxes_diagram(
        "architecture.png",
        "Production architecture: eu-west-1 primary, us-east-1 failover",
        {"Load balancer": (310, 60), "eu-west-1 (primary)": (90, 200), "us-east-1 (failover)": (530, 200)},
        [("Load balancer", "eu-west-1 (primary)"), ("Load balancer", "us-east-1 (failover)")],
    )
    boxes_diagram(
        "codebase-map.png",
        "Codebase map: the gateway routes to accounts, orders and notify",
        {"gateway": (310, 60), "accounts": (90, 220), "orders": (310, 220), "notify": (530, 220)},
        [("gateway", "accounts"), ("gateway", "orders"), ("gateway", "notify")],
    )
    boxes_diagram(
        "q1-timeline.png",
        "Q1 2027 timeline",
        {"Jan: SSO design": (40, 150), "Feb 15: v3 beta": (310, 150), "Mar: latency work": (580, 150)},
        [("Jan: SSO design", "Feb 15: v3 beta"), ("Feb 15: v3 beta", "Mar: latency work")],
    )
    (OUT / "rollback.sh").write_text(
        "#!/usr/bin/env bash\n"
        "# Roll production back to a previous release tag (fictional example).\n"
        "# Usage: ./rollback.sh v2.9.3\n"
        "set -euo pipefail\n"
        'echo "Rolling back to ${1:?release tag required}"\n'
    )
    (OUT / "expense-claim-template.csv").write_text(
        "date,category,description,amount_usd,receipt_attached\n"
        "2026-11-02,Taxi,Airport to office,42.50,yes\n"
    )


if __name__ == "__main__":
    main()
