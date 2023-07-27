<?php

namespace App\Application\Responses;

class ValidateBasketResponse
{
    public bool $isValidated = false;
    public ?string $orderId = null;
    public float $finalAmount = 0.0;
}