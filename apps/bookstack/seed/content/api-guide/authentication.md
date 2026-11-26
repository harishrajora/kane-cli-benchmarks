The API uses bearer tokens. Every request must include an API key.

## Sending your key

```http
GET /v2/users HTTP/1.1
Host: api.northwind.example
Authorization: Bearer nw_live_xxxxxxxxxxxx
```

## Key types

| Prefix | Environment | Expires |
| --- | --- | --- |
| `nw_test_` | Sandbox | Never |
| `nw_live_` | Production | After 90 days |

Keys can be revoked at any time from the developer settings. A revoked key returns error `401 AUTH_REVOKED`.
