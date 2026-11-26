Limits apply per API key.

| Plan | Requests per minute | Requests per hour |
| --- | --- | --- |
| Free | 60 | 1,000 |
| Team | 120 | 5,000 |
| Enterprise | 600 | 50,000 |

Requests over the limit receive HTTP `429 Too Many Requests` with a `Retry-After` header giving the number of seconds to wait.

> Limits were raised for the Team plan in v2.10. See [Release Notes v2.10](page:release-notes/release-notes-v2-10).
