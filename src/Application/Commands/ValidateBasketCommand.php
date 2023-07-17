<?php

namespace App\Application\Commands;

use App\Application\ValueObjects\Id;

class ValidateBasketCommand
{
    /**
     * @param string $id
     * @param int $paymentMethod
     * @param int $currency
     */
    public function __construct(
        public string        $id,
        public int $paymentMethod,
        public int $currency
    )
    {
    }
}