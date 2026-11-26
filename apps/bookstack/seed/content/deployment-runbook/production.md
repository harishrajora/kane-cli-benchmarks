Production serves all customers. Changes need an approved [Release Checklist](page:deployment-runbook/release-checklist).

![Production architecture](asset:architecture.png)

| Item | Value |
| --- | --- |
| URL | `https://app.northwind.example` |
| Regions | eu-west-1 (primary), us-east-1 (failover) |
| Instances | 12 |
| Uptime target | 99.95% per month |

Only the Platform team can deploy to production.
