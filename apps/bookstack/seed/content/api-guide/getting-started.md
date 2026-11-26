Welcome to the Northwind API. This page gets you from zero to your first successful request.

## Base URL

All requests go to `https://api.northwind.example/v2`.

## Your first request

1. Create an API key in **Settings → Developer → API keys**.
2. Send it in the `Authorization` header (see [Authentication](page:api-guide/authentication)).
3. Call the health endpoint:

```bash
curl -H "Authorization: Bearer $NW_KEY" https://api.northwind.example/v2/health
```

A healthy response returns `{"status": "ok"}`.

## Next steps

- Read the [Rate Limits](page:api-guide/rate-limits) before going to production.
- Browse the [Users Endpoint](page:api-guide/users-endpoint).
