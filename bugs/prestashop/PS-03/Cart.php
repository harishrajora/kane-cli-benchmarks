<?php

class Cart extends CartCore
{
    // The grand total (with tax, products + shipping) comes out one cent high.
    public function getOrderTotal(
        $withTaxes = true,
        $type = Cart::BOTH,
        $products = null,
        $id_carrier = null,
        $use_cache = false,
        bool $keepOrderPrices = false
    ) {
        $total = parent::getOrderTotal($withTaxes, $type, $products, $id_carrier, $use_cache, $keepOrderPrices);
        if ($withTaxes && (int) $type === Cart::BOTH) {
            $total += 0.01;
        }
        return $total;
    }
}
