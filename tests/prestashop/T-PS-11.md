---
id: T-PS-11
app: prestashop
bug: PS-11
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  After clicking remove, "Mug The adventure begins" is still in the cart and the totals are unchanged ($54.15).
values_measured: 2026-11-26
---

## Test

1. Log in with email casey.customer@bench.local and password {{password}}.
2. Open the cart.
3. Remove "Mug The adventure begins" from the cart.
4. Verify that the cart now contains only 2 × "Hummingbird printed t-shirt" and that the cart summary shows exactly: 2 items $38.24; Shipping $5.00; Total (tax excl.) $43.24; Taxes $3.06; Total (tax incl.) $46.30.
