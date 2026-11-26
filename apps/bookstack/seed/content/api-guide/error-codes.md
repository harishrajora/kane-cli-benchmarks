Errors return a JSON body with a `code` and a `message`.

| HTTP status | Code | Meaning |
| --- | --- | --- |
| 400 | `VALIDATION_FAILED` | A field is missing or has the wrong type |
| 401 | `AUTH_MISSING` | No API key was sent |
| 401 | `AUTH_REVOKED` | The API key was revoked |
| 403 | `FORBIDDEN` | The key's user lacks permission |
| 404 | `NOT_FOUND` | The resource does not exist |
| 429 | `RATE_LIMITED` | Too many requests; see [Rate Limits](page:api-guide/rate-limits) |
| 500 | `INTERNAL` | Something went wrong on our side |
