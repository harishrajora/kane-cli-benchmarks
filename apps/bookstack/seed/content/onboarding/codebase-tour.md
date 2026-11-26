The platform is split into four services.

![Codebase map](asset:codebase-map.png)

| Service | Language | Owns |
| --- | --- | --- |
| `gateway` | Go | Routing and authentication |
| `accounts` | Python | Users and workspaces |
| `orders` | Java | Orders and payments |
| `notify` | TypeScript | Emails and webhooks |

The public API they expose is documented in the [API Guide](page:api-guide/getting-started).
