---
id: T-PS-08
app: prestashop
bug: PS-08
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  The poster's price shows $24.65 instead of $29.00: a 15% discount Casey is not entitled to.
values_measured: 2026-11-26
---

## Test

Casey Customer is a regular customer and pays catalogue prices.

1. Log in with email casey.customer@bench.local and password {{password}}.
2. Open the product page of "The best is yet to come' Framed poster".
3. Verify that, with the default dimension 40x60cm selected, the price shown is $29.00.
