<?php
/**
 * Start PrestaShop for a bug script the way its back office does (same as apps/prestashop/seed/seed.php),
 * with a shop, language, currency and US country in context. Included by the PHP files in bugs/prestashop.
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

function bench_customer(string $email): Customer {
    $c = (new Customer())->getByEmail($email);
    if (!$c) { fwrite(STDERR, "missing customer $email\n"); exit(1); }
    return $c;
}

/** A throwaway cart for $cu with [product_id, qty] items and the named carrier. Run reset afterwards. */
function bench_cart(Customer $cu, string $carrier, array $items): Cart {
    $ctx = Context::getContext();
    $idCarrier = 0;
    foreach (Carrier::getCarriers($ctx->language->id, true) as $c) { if ($c['name'] === $carrier) { $idCarrier = (int) $c['id_carrier']; } }
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
    foreach ($items as [$pid, $qty]) { $cart->updateQty($qty, $pid, 0); }
    $cart->setDeliveryOption([$addr => $idCarrier . ',']);
    $cart->update();
    return $cart;
}
