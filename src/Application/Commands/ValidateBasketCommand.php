<?php

namespace App\Application\Commands;

class ValidateBasketCommand
{
    /**
     * @param string $basketId
     * @param int $paymentMethod
     * @param int $currency
     */
    public function __construct(
        public string $basketId,
        public int    $paymentMethod,
        public int    $currency
    )
    {
    }
}