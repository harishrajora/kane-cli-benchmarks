Create and track orders.

## Create an order

`POST /v2/orders`

| Field | Type | Required |
| --- | --- | --- |
| `customer_id` | integer | Yes |
| `items` | array | Yes, at least 1 |
| `currency` | string | No, defaults to `USD` |

## Order statuses

1. `pending`
2. `paid`
3. `shipped`
4. `delivered`

An order can be cancelled only while it is `pending` or `paid`.
