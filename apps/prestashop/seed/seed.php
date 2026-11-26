<?php
/**
 * Seed PrestaShop with the benchmark's fixed data, using PrestaShop's own classes
 * (the same code the back office runs), so every value is validated and related data is filled in.
 *
 * Reads structure.json next to this file. Run inside the PrestaShop container through ../seed.sh,
 * which copies this folder in and passes USER_PASSWORD (password for seeded customers and staff).
 * Refuses to run twice: it stops if a seeded customer already exists.
 */

use PrestaShop\PrestaShop\Core\Crypto\Hashing;

// Boot PrestaShop the way its own back office does (admin-bench/index.php): config, then the admin
// Symfony kernel. Cart and order code needs the kernel's service container, even from the command line.
define('_PS_ADMIN_DIR_', '/var/www/html/admin-bench');
require '/var/www/html/config/config.inc.php';
define('_PS_APP_ID_', AdminKernel::APP_ID);
require_once '/var/www/html/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv('/var/www/html/.env');
global $kernel;
$kernel = new AdminKernel(_PS_ENV_, _PS_MODE_DEV_);
$kernel->boot();

$spec = json_decode(file_get_contents(__DIR__ . '/structure.json'), true);
$password = getenv('USER_PASSWORD') ?: exit("USER_PASSWORD is not set\n");

function fail(string $msg): void { fwrite(STDERR, "ERROR: $msg\n"); exit(1); }
function say(string $msg): void { echo $msg, "\n"; }
function langs(string $value): array {
    $out = [];
    foreach (Language::getIDs(false) as $id) { $out[$id] = $value; }
    return $out;
}
function save(ObjectModel $o, string $what): void {
    $ok = $o->id ? $o->update() : $o->add();
    if (!$ok) { fail("could not save $what"); }
}

// ---------- context ----------
Shop::setContext(Shop::CONTEXT_SHOP, 1);
$ctx = Context::getContext();
$ctx->shop = new Shop(1);
$ctx->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
$ctx->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
$ctx->link = new Link();
$ctx->employee = new Employee(1);
$idLang = (int) $ctx->language->id;
$hashing = new Hashing();

$usId = (int) Country::getByIso($spec['tax']['country_iso']);
$ctx->country = new Country($usId);
$usZone = (int) Country::getIdZone($usId);

foreach ($spec['customers'] as $c) {
    if (Customer::customerExists($c['email'])) { fail("{$c['email']} already exists: seed only runs once; reset first"); }
}

// ---------- shop settings ----------
Configuration::updateValue('PS_SHOP_NAME', $spec['shop']['name']);
Configuration::updateValue('PS_CART_FOLLOWING', $spec['shop']['restore_cart_on_login'] ? 1 : 0);
Configuration::updateValue('PS_MAIL_METHOD', 3); // never send email: the benchmark shop must not send mail
say("shop     name '{$spec['shop']['name']}', restore cart on login on, outgoing email off");

// ---------- tax ----------
$t = $spec['tax'];
$tax = new Tax();
$tax->name = langs($t['tax_name']);
$tax->rate = $t['rate'];
$tax->active = 1;
save($tax, 'tax');
$group = new TaxRulesGroup();
$group->name = $t['group_name'];
$group->active = 1;
save($group, 'tax rules group');
$rule = new TaxRule();
$rule->id_tax_rules_group = $group->id;
$rule->id_country = $usId;
$rule->id_state = 0; // all states
$rule->zipcode_from = 0;
$rule->zipcode_to = 0;
$rule->behavior = 0;
$rule->id_tax = $tax->id;
$rule->description = '';
save($rule, 'tax rule');
$n = 0;
foreach (Db::getInstance()->executeS('SELECT id_product FROM ' . _DB_PREFIX_ . 'product ORDER BY id_product') as $row) {
    $p = new Product((int) $row['id_product']);
    $p->id_tax_rules_group = $group->id;
    save($p, "product {$row['id_product']}");
    $n++;
}
say("tax      {$t['group_name']} for all US states, applied to $n products");

// ---------- customer groups (before carriers, which list the groups allowed to use them) ----------
$groupId = ['Customer' => (int) Configuration::get('PS_CUSTOMER_GROUP')];
$moduleIds = array_map(fn ($m) => (int) $m['id_module'], Module::getModulesInstalled());
foreach ($spec['groups'] as $g) {
    $grp = new Group();
    $grp->name = langs($g['name']);
    $grp->reduction = $g['discount_percent'];
    $grp->price_display_method = (int) Group::getPriceDisplayMethod($groupId['Customer']);
    $grp->show_prices = 1;
    save($grp, "group {$g['name']}");
    $groupId[$g['key']] = (int) $grp->id;
    // like the back office: the new group may see every category and use every module (payments included)
    foreach (Category::getSimpleCategories($idLang) as $cat) {
        (new Category((int) $cat['id_category']))->addGroupsIfNoExist($grp->id);
    }
    (new Category((int) Configuration::get('PS_ROOT_CATEGORY')))->addGroupsIfNoExist($grp->id);
    (new Category((int) Configuration::get('PS_HOME_CATEGORY')))->addGroupsIfNoExist($grp->id);
    Group::addModulesRestrictions($grp->id, $moduleIds, [1]);
    say("group    {$g['name']} ({$g['discount_percent']}% off)");
}
$allGroups = array_map(fn ($g) => (int) $g['id_group'], Group::getGroups($idLang));

// ---------- carriers ----------
if ($spec['carriers']['disable_existing']) {
    foreach (Carrier::getCarriers($idLang, true, false, false, null, Carrier::ALL_CARRIERS) as $row) {
        $old = new Carrier((int) $row['id_carrier']);
        $old->active = 0;
        save($old, "carrier {$row['name']}");
        say("carrier  disabled {$row['name']}");
    }
}
$carrierId = [];
foreach ($spec['carriers']['create'] as $cdef) {
    $car = new Carrier();
    $car->name = $cdef['name'];
    $car->delay = langs($cdef['delay']);
    $car->active = 1;
    $car->is_free = 0;
    $car->shipping_handling = 0;
    $car->shipping_method = Carrier::SHIPPING_METHOD_PRICE;
    $car->range_behavior = 0;
    $car->need_range = 1;
    $car->id_tax_rules_group = 0; // shipping is not taxed
    $car->url = '';
    save($car, "carrier {$cdef['name']}");
    $car->addZone($usZone);
    $car->setGroups($allGroups);
    // Same as the back office's carrier wizard: create the ranges (each auto-adds a $0 price row),
    // delete those placeholder rows, then write the real prices.
    $prices = [];
    foreach ($cdef['ranges'] as $r) {
        $range = new RangePrice();
        $range->id_carrier = $car->id;
        $range->delimiter1 = $r['from'];
        $range->delimiter2 = $r['to'];
        save($range, 'price range');
        $prices[] = ['id_range_price' => (int) $range->id, 'id_range_weight' => null,
                     'id_carrier' => (int) $car->id, 'id_zone' => $usZone, 'price' => $r['price']];
    }
    $car->deleteDeliveryPrice('range_price');
    $car->addDeliveryPrice($prices, true) ?: fail("could not set prices for carrier {$cdef['name']}");
    $carrierId[$cdef['name']] = (int) $car->id;
    say("carrier  {$cdef['name']}");
}

// ---------- product stock and quantity rules ----------
foreach ($spec['products'] as $pdef) {
    if (isset($pdef['quantity'])) {
        StockAvailable::setQuantity($pdef['id'], 0, $pdef['quantity']);
        say("product  {$pdef['name']}: stock {$pdef['quantity']}");
    }
    if (isset($pdef['minimal_quantity'])) {
        $p = new Product($pdef['id']);
        $p->minimal_quantity = $pdef['minimal_quantity'];
        save($p, "product {$pdef['id']}");
        say("product  {$pdef['name']}: minimum quantity {$pdef['minimal_quantity']}");
    }
}

// ---------- vouchers ----------
foreach ($spec['vouchers'] as $v) {
    $cr = new CartRule();
    $cr->name = langs($v['name']);
    $cr->code = $v['code'];
    $cr->date_from = $v['valid_from'] . ' 00:00:00';
    $cr->date_to = $v['valid_to'] . ' 23:59:59';
    $cr->quantity = 10000;
    $cr->quantity_per_user = 10000;
    $cr->active = 1;
    $cr->highlight = 0;
    $cr->partial_use = 0;
    $cr->priority = 1;
    $cr->reduction_currency = $ctx->currency->id;
    $cr->minimum_amount_currency = $ctx->currency->id;
    if (isset($v['percent'])) { $cr->reduction_percent = $v['percent']; }
    if (isset($v['amount'])) { $cr->reduction_amount = $v['amount']; $cr->reduction_tax = 1; }
    if (isset($v['minimum_amount'])) { $cr->minimum_amount = $v['minimum_amount']; $cr->minimum_amount_tax = 0; }
    save($cr, "voucher {$v['code']}");
    say("voucher  {$v['code']} ({$v['name']})");
}

// ---------- demo customer ----------
foreach (Customer::getCustomersByEmail($spec['demo_customer']['email']) as $row) {
    $demo = new Customer((int) $row['id_customer']);
    $demo->active = 0;
    $demo->passwd = $hashing->hash(bin2hex(random_bytes(16)));
    save($demo, 'demo customer');
    say("customer deactivated demo customer {$demo->email} (its demo orders are kept)");
}

// ---------- customers and addresses ----------
$customer = [];
$address = [];
foreach ($spec['customers'] as $c) {
    $groups = array_map(fn ($k) => $groupId[$k], $c['groups']);
    $cu = new Customer();
    $cu->firstname = $c['firstname'];
    $cu->lastname = $c['lastname'];
    $cu->email = $c['email'];
    $cu->passwd = $hashing->hash($password);
    $cu->id_default_group = $groups[0];
    $cu->active = 1;
    $cu->newsletter = 0;
    $cu->optin = 0;
    save($cu, "customer {$c['email']}");
    $cu->updateGroup($groups);
    $a = new Address();
    $a->id_customer = $cu->id;
    $a->id_country = $usId;
    $a->id_state = (int) State::getIdByIso($c['address']['state_iso'], $usId);
    $a->alias = $c['address']['alias'];
    $a->firstname = $c['firstname'];
    $a->lastname = $c['lastname'];
    $a->address1 = $c['address']['address1'];
    $a->city = $c['address']['city'];
    $a->postcode = $c['address']['postcode'];
    $a->phone = $c['address']['phone'];
    save($a, "address for {$c['email']}");
    $customer[$c['key']] = $cu;
    $address[$c['key']] = $a;
    say("customer {$c['firstname']} {$c['lastname']} <{$c['email']}>, {$c['address']['city']}, {$c['address']['state_iso']}");
}

// ---------- carts and orders ----------
function attributeId(int $productId, array $item, int $idLang): int {
    $want = array_filter(['Size' => $item['size'] ?? null, 'Color' => $item['color'] ?? null]);
    if (!$want) { return 0; }
    // getAttributeCombinations() lists one attribute group per combination, so read each
    // combination's full attribute list and match on the values (e.g. "L" and "Black").
    $seen = [];
    foreach ((new Product($productId))->getAttributeCombinations($idLang) as $row) {
        $ipa = (int) $row['id_product_attribute'];
        if (isset($seen[$ipa])) { continue; }
        $seen[$ipa] = true;
        $names = array_column((new Combination($ipa))->getAttributesName($idLang), 'name');
        if (!array_diff(array_values($want), $names)) { return $ipa; }
    }
    fail("no combination of product $productId matching " . json_encode($want));
}

function makeCart(Customer $cu, Address $a, int $carrier, array $items, int $idLang): Cart {
    $ctx = Context::getContext();
    $cart = new Cart();
    $cart->id_customer = $cu->id;
    $cart->id_address_delivery = $a->id;
    $cart->id_address_invoice = $a->id;
    $cart->id_currency = $ctx->currency->id;
    $cart->id_lang = $idLang;
    $cart->id_shop = 1;
    $cart->id_shop_group = 1;
    $cart->id_carrier = $carrier;
    $cart->secure_key = $cu->secure_key;
    // Storefront carts always belong to a visitor ("guest") record, and PrestaShop only restores a
    // saved cart on login when it has one. Reuse the customer's guest record, or create it.
    $idGuest = (int) Guest::getFromCustomer($cu->id);
    if (!$idGuest) {
        $guest = new Guest();
        $guest->id_customer = $cu->id;
        $guest->accept_language = 'en';
        save($guest, "guest for {$cu->email}");
        $idGuest = (int) $guest->id;
    }
    $cart->id_guest = $idGuest;
    save($cart, 'cart');
    $ctx->cart = $cart;
    $ctx->customer = $cu;
    foreach ($items as $it) {
        $ok = $cart->updateQty($it['quantity'], $it['product_id'], attributeId($it['product_id'], $it, $idLang));
        if ($ok !== true) { fail("could not add product {$it['product_id']} to cart"); }
    }
    $cart->setDeliveryOption([$a->id => $carrier . ',']);
    save($cart, 'cart');
    return $cart;
}

$wire = Module::getInstanceByName('ps_wirepayment');
$state = [
    'Payment accepted' => (int) Configuration::get('PS_OS_PAYMENT'),
    'Shipped' => (int) Configuration::get('PS_OS_SHIPPING'),
    'Delivered' => (int) Configuration::get('PS_OS_DELIVERED'),
    'Awaiting bank wire payment' => (int) Configuration::get('PS_OS_BANKWIRE'),
];
$path = [ // order statuses each order passes through, like a real order
    'Delivered' => ['Payment accepted', 'Shipped', 'Delivered'],
    'Shipped' => ['Payment accepted', 'Shipped'],
    'Awaiting bank wire payment' => ['Awaiting bank wire payment'],
];
foreach ($spec['orders'] as $o) {
    $cu = $customer[$o['customer']];
    $cart = makeCart($cu, $address[$o['customer']], $carrierId[$o['carrier']], $o['items'], $idLang);
    $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
    $steps = $path[$o['status']];
    $wire->validateOrder($cart->id, $state[$steps[0]], $total, $wire->displayName, null, [], null, false, $cu->secure_key);
    $order = Order::getByCartId($cart->id) ?: fail('order was not created');
    foreach (array_slice($steps, 1) as $s) {
        $h = new OrderHistory();
        $h->id_order = $order->id;
        $h->id_employee = 1;
        $h->changeIdOrderState($state[$s], $order, true);
        save($h, 'order status');
    }
    say(sprintf('order    %s for %s: %s, %s, total $%.2f', $order->reference, $cu->firstname, $o['carrier'], $o['status'], $total));
}

foreach ($spec['saved_carts'] as $sc) {
    $cart = makeCart($customer[$sc['customer']], $address[$sc['customer']], $carrierId['Standard'], $sc['items'], $idLang);
    say(sprintf('cart     saved cart for %s: %d items, subtotal $%.2f before tax',
        $customer[$sc['customer']]->firstname, $cart->nbProducts(), $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS)));
}

// ---------- back-office staff ----------
foreach ($spec['employees'] as $e) {
    $profileId = 0;
    foreach (Profile::getProfiles($idLang) as $p) {
        if ($p['name'] === $e['profile']) { $profileId = (int) $p['id_profile']; }
    }
    $profileId ?: fail("profile {$e['profile']} not found");
    $em = new Employee();
    $em->firstname = $e['firstname'];
    $em->lastname = $e['lastname'];
    $em->email = $e['email'];
    $em->passwd = $hashing->hash($password);
    $em->id_profile = $profileId;
    $em->id_lang = $idLang;
    $em->active = 1;
    $em->default_tab = (int) Tab::getIdFromClassName('AdminOrders');
    save($em, "employee {$e['email']}");
    say("staff    {$e['firstname']} {$e['lastname']} <{$e['email']}>, profile {$e['profile']}");
}

Tools::clearAllCache();
say('Seed complete.');
