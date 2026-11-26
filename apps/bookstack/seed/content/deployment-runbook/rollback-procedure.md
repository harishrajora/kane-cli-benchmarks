Use this when a release causes errors in production.

1. Announce the rollback in `#incidents`.
2. Run the attached `rollback.sh` with the previous release tag.
3. Confirm the error rate drops below 0.5% within 10 minutes.
4. Open an incident report (see [Incident Response](page:deployment-runbook/incident-response)).

A rollback must start within **15 minutes** of the decision.
