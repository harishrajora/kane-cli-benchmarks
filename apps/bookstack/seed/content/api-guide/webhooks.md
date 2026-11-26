**Draft:** this page is being written and may change.

Webhooks notify your server when something happens in Northwind.

## Events

- `order.created`
- `order.shipped`
- `user.deleted`

Each delivery is signed with an `X-Northwind-Signature` header. Failed deliveries are retried **5 times** over 24 hours.
