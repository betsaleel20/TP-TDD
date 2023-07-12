<?php

namespace App\Application\Commands;

use App\Application\ValueObjects\Id;

class ValidateOrderCommand
{
    /**
     * @param Id|null $id
     */
    public function __construct(
        public string        $id,
        public int $paymentMethod,
        public int $currency
    )
    {
    }
}