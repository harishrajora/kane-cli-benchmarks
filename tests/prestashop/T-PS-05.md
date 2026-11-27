---
id: T-PS-05
app: prestashop
bug: PS-05
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  The promo code EXPIRED is accepted and a discount is applied, instead of being refused with "This voucher has expired".
values_measured: 2026-11-26
---

## Test

The promo code EXPIRED belongs to a summer sale that ended on 30 June 2026.

1. Log in with email casey.customer@bench.local and password {{password}}.
2. Open the cart.
3. Enter the promo code EXPIRED and apply it.
4. Verify that the code is refused with the message "This voucher has expired", that no discount is added to the cart, and that Total (tax incl.) is still $54.15.
