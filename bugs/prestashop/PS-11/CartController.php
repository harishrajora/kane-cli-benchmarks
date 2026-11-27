<?php

class CartController extends CartControllerCore
{
    // Removing a product from the cart has no effect.
    protected function processDeleteProductInCart(): void
    {
    }
}
