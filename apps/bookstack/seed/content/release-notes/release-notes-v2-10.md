Released 2026-10-28.

## New

- Team plan rate limit raised from 90 to **120 requests per minute** ([Rate Limits](page:api-guide/rate-limits)).
- Webhook deliveries now include an `X-Northwind-Delivery-Id` header.

## Fixed

- Orders with more than 100 items no longer time out.
