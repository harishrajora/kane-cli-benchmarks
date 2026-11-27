<?php
require __DIR__ . '/bootstrap.php';
$casey = bench_customer('casey.customer@bench.local');
$wholesale = (int) Db::getInstance()->getValue('SELECT id_group FROM ' . _DB_PREFIX_ . "group_lang WHERE name = 'Wholesale'");
$casey->updateGroup([(int) Configuration::get('PS_CUSTOMER_GROUP'), $wholesale]);
$casey->id_default_group = $wholesale;
$casey->update() || exit(1);
