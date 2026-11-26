Manage the users in your Northwind workspace.

## List users

`GET /v2/users` returns users in pages of 25. Use `?page=2` for the next page.

## Get one user

`GET /v2/users/{id}`

```json
{
  "id": 4127,
  "name": "Ada Example",
  "email": "ada@example.com",
  "role": "member",
  "created_at": "2026-03-14T09:30:00Z"
}
```

## Roles

A user's `role` is one of `owner`, `admin` or `member`. Only owners can change roles.
