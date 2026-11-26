<?php
/**
 * Check the seeded shop's cart rules to the cent, using PrestaShop's own cart code.
 * Creates throwaway carts: run ../reset.sh afterwards. Run through ../verify.sh.
 * Prints one line per check: PASS/FAIL, expected, actual.
 */

define('_PS_ADMIN_DIR_', '/var/www/html/admin-bench');
require '/var/www/html/config/config.inc.php';
define('_PS_APP_ID_', AdminKernel::APP_ID);
require_once '/var/www/html/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv('/var/www/html/.env');
global $kernel;
$kernel = new AdminKernel(_PS_ENV_, _PS_MODE_DEV_);
$kernel->boot();

Shop::setContext(Shop::CONTEXT_SHOP, 1);
$ctx = Context::getContext();
$ctx->shop = new Shop(1);
$ctx->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
$ctx->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
$ctx->link = new Link();
$ctx->country = new Country((int) Country::getByIso('US'));

$fails = 0;
function check(string $what, $expected, $actual): void {
    global $fails;
    $ok = is_float($expected) ? abs($expected - (float) $actual) < 0.005 : $expected === $actual;
    if (!$ok) { $fails++; }
    $fmt = fn ($v) => is_float($v) ? sprintf('%.2f', $v) : var_export($v, true);
    printf("%s  %-62s expected %-8s got %s\n", $ok ? 'PASS' : 'FAIL', $what, $fmt($expected), $fmt($actual));
}

function customer(string $email): Customer {
    $c = new Customer();
    return $c->getByEmail($email) ?: exit("missing customer $email\n");
}

function carrierId(string $name): int {
    foreach (Carrier::getCarriers(1, true) as $c) { if ($c['name'] === $name) { return (int) $c['id_carrier']; } }
    exit("missing carrier $name\n");
}

function cart(Customer $cu, string $carrier, array $items) {
    $ctx = Context::getContext();
    $addr = Address::getFirstCustomerAddressId($cu->id);
    $cart = new Cart();
    $cart->id_customer = $cu->id;
    $cart->id_address_delivery = $addr;
    $cart->id_address_invoice = $addr;
    $cart->id_currency = $ctx->currency->id;
    $cart->id_lang = $ctx->language->id;
    $cart->id_shop = 1;
    $cart->id_shop_group = 1;
    $cart->secure_key = $cu->secure_key;
    $cart->add();
    $ctx->cart = $cart;
    $ctx->customer = $cu;
    $results = [];
    foreach ($items as [$pid, $qty]) { $results[] = $cart->updateQty($qty, $pid, 0); }
    $cart->setDeliveryOption([$addr => carrierId($carrier) . ',']);
    $cart->update();
    return [$cart, $results];
}

function totals(Cart $c): array {
    return [
        'products' => (float) $c->getOrderTotal(false, Cart::ONLY_PRODUCTS),
        'shipping' => (float) $c->getOrderTotal(false, Cart::ONLY_SHIPPING),
        'discount' => (float) $c->getOrderTotal(true, Cart::ONLY_DISCOUNTS),
        'total' => (float) $c->getOrderTotal(true, Cart::BOTH),
    ];
}

/** Apply a voucher like the cart page does. Returns the reloaded cart, or the refusal message. */
function voucher(Cart $c, string $code) {
    $id = CartRule::getIdByCode($code);
    $error = (new CartRule($id))->checkValidity(Context::getContext(), false, true);
    if ($error) { return (string) $error; }
    $c->addCartRule($id);
    Cache::clean('*');
    CartRule::resetStaticCache();
    $reloaded = new Cart($c->id);
    Context::getContext()->cart = $reloaded;
    return $reloaded;
}

$casey = customer('casey.customer@bench.local');
$wes = customer('wes.wholesale@bench.local');
// Product ids: 6 mug (min qty 2), 7 mug (3 in stock), 8 mug (out of stock), 3 poster $29, 16 notebook $12.90, 9 cushion $18.90

// 1. Basic price, tax and Standard shipping under $50
[$c] = cart($casey, 'Standard', [[7, 1]]);
$t = totals($c);
check('Casey, 1 mug $11.90, Standard: products', 11.90, $t['products']);
check('  shipping under $50', 5.00, $t['shipping']);
check('  total = (11.90 + 5.00) + 8% tax on 11.90 = 17.85', 17.85, $t['total']);

// 2. Free-shipping threshold: before or after tax? 4 x $11.90 = $47.60 before tax, $51.41 after
[$c] = cart($casey, 'Standard', [[7, 3], [6, 2]]); // 3 + 2 mugs at 11.90 = 59.50: over 50 either way
check('Casey, 5 mugs = 59.50: free Standard shipping', 0.00, totals($c)['shipping']);
[$c] = cart($casey, 'Standard', [[6, 2], [7, 2]]); // 47.60 before tax, 51.41 after
$t = totals($c);
check('Casey, 4 mugs = 47.60 before tax / 51.41 after: products', 47.60, $t['products']);
printf("INFO  threshold test: shipping on 47.60 before tax / 51.41 after = %.2f (5.00 means before tax, 0.00 means after tax)\n", $t['shipping']);

// 3. Express always $15
[$c] = cart($casey, 'Express', [[3, 2]]);
$t = totals($c);
check('Casey, 2 posters $58, Express: shipping', 15.00, $t['shipping']);
check('  total = 58.00 x 1.08 + 15.00 = 77.64', 77.64, $t['total']);

// 4. Vouchers
[$c] = cart($casey, 'Express', [[3, 2]]);
$c = voucher($c, 'BENCH10');
check('BENCH10 accepted', true, $c instanceof Cart);
$t = totals($c);
check('  BENCH10 discount (10% of 58.00 incl. tax 62.64) = 6.26', 6.26, $t['discount']);
check('  total = 62.64 - 6.26 + 15.00 = 71.38', 71.38, $t['total']);
[$c] = cart($casey, 'Express', [[3, 2]]);
$c = voucher($c, 'SAVE5');
check('SAVE5 accepted on 58.00', true, $c instanceof Cart);
check('  SAVE5 total = 62.64 - 5.00 + 15.00 = 72.64', 72.64, totals($c)['total']);
[$c] = cart($casey, 'Express', [[7, 2]]);
$r = voucher($c, 'SAVE5');
check('SAVE5 refused on 23.80 (minimum 30)', true, is_string($r));
is_string($r) && print("INFO  SAVE5 refusal message: $r\n");
$r = voucher($c, 'EXPIRED');
check('EXPIRED refused', true, is_string($r));
is_string($r) && print("INFO  EXPIRED refusal message: $r\n");

// 5. Wholesale group price
[$c] = cart($wes, 'Standard', [[3, 1]]);
$t = totals($c);
check('Wes, 1 poster $29 less 15% Wholesale: products', 24.65, $t['products']);
check('  total = 24.65 x 1.08 + 5.00 = 31.62', 31.62, $t['total']);

// 6. Stock and quantity rules
[, $r] = cart($casey, 'Standard', [[8, 1]]);
check('Out-of-stock mug refused', true, $r[0] !== true);
[, $r] = cart($casey, 'Standard', [[7, 4]]);
check('4 of the low-stock mug (3 left) refused', true, $r[0] !== true);
[, $r] = cart($casey, 'Standard', [[6, 1]]);
check('1 of the minimum-2 mug refused', true, $r[0] !== true);
[, $r] = cart($casey, 'Standard', [[6, 2]]);
check('2 of the minimum-2 mug accepted', true, $r[0] === true);

echo $fails ? "\n$fails check(s) failed\n" : "\nAll checks passed\n";
exit($fails ? 1 : 0);
