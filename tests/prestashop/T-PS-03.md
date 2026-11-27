---
id: T-PS-03
app: prestashop
bug: PS-03
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  Total (tax incl.) is $54.16 instead of $54.15, while the other summary lines are correct.
values_measured: 2026-11-26
---

## Test

1. Log in with email casey.customer@bench.local and password {{password}}.
2. Open the cart.
3. Verify that the cart contains 2 × "Hummingbird printed t-shirt" (size L, color Black) and 1 × "Mug The adventure begins".
4. Verify that the cart summary shows exactly: 3 items $50.14; Shipping Free; Total (tax excl.) $50.14; Taxes $4.01; Total (tax incl.) $54.15.
