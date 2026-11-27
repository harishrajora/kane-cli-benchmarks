<?php
require __DIR__ . '/bootstrap.php';
$id = (int) Db::getInstance()->getValue('SELECT tl.id_tax FROM ' . _DB_PREFIX_ . 'tax_lang tl JOIN ' . _DB_PREFIX_ . "tax t ON t.id_tax = tl.id_tax AND t.deleted = 0 WHERE tl.name = 'US Sales Tax'");
$tax = new Tax($id);
$tax->rate = 0.8;
$tax->update() || exit(1);
