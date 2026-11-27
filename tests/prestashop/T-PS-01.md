---
id: T-PS-01
app: prestashop
bug: PS-01
start_url: http://localhost:8080/login
expected:
  clean: PASS
  bug: FAIL
right_reason: >-
  Taxes are $0.20 instead of $1.97 (0.8% instead of 8%), so Total (tax incl.) is $29.85 instead of $31.62.
values_measured: 2026-11-26
---

## Test

1. Log in with email wes.wholesale@bench.local and password {{password}}.
2. Find the product "The best is yet to come' Framed poster" and add 1 to the cart, keeping the default dimension 40x60cm.
3. Open the cart.
4. Verify that the cart summary shows exactly: 1 item $24.65; Shipping $5.00; Total (tax excl.) $29.65; Taxes $1.97; Total (tax incl.) $31.62.
