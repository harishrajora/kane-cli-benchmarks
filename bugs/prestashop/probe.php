<?php
/**
 * Report the shop facts that the PrestaShop bugs change, as JSON. Used by check-clean.sh and the bugs' check.sh.
 * Builds throwaway carts and deletes them again, so Casey's saved cart stays her most recent cart.
 */
require __DIR__ . '/bootstrap.php';

$casey = bench_customer('casey.customer@bench.local');
$facts = [];

$taxId = (int) Db::getInstance()->getValue('SELECT tl.id_tax FROM ' . _DB_PREFIX_ . 'tax_lang tl JOIN ' . _DB_PREFIX_ . "tax t ON t.id_tax = tl.id_tax AND t.deleted = 0 WHERE tl.name = 'US Sales Tax'");
$facts['tax_rate'] = (float) (new Tax($taxId))->rate;

$group = new Group((int) Customer::getDefaultGroupId($casey->id));
$facts['casey_default_group'] = $group->name[(int) Context::getContext()->language->id];
$facts['casey_groups'] = array_map('intval', $casey->getGroups());

// 1 x "Mug The adventure begins" ($11.90) with Standard shipping: clean total 11.90 * 1.08 + 5.00 = 17.85
$cart = bench_cart($casey, 'Standard', [[7, 1]]);
$facts['mug_cart'] = [
    'products' => round((float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS), 2),
    'tax' => round((float) $cart->getOrderTotal(true, Cart::BOTH) - (float) $cart->getOrderTotal(false, Cart::BOTH), 2),
    'shipping' => round((float) $cart->getOrderTotal(false, Cart::ONLY_SHIPPING), 2),
    'total' => round((float) $cart->getOrderTotal(true, Cart::BOTH), 2),
];
$cart->delete();

// Voucher EXPIRED (ended 2026-06-30) on 2 posters ($58.00)
$cart = bench_cart($casey, 'Express', [[3, 2]]);
$rule = new CartRule((int) CartRule::getIdByCode('EXPIRED'));
$facts['expired_voucher_accepted'] = ($rule->checkValidity(Context::getContext(), false, false) === true);
$cart->delete();

echo json_encode($facts), "\n";
