<?php

class CartRule extends CartRuleCore
{
    // Vouchers past their end date are treated as still valid.
    public function checkValidity(Context $context, $alreadyInCart = false, $display_error = true, $check_carrier = true, $useOrderPrices = false)
    {
        $dateTo = $this->date_to;
        if (!empty($dateTo) && strtotime($dateTo) < time()) {
            $this->date_to = date('Y-m-d H:i:s', time() + 86400);
        }
        $result = parent::checkValidity($context, $alreadyInCart, $display_error, $check_carrier, $useOrderPrices);
        $this->date_to = $dateTo;
        return $result;
    }
}
