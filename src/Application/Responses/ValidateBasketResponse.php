<?php

namespace App\Application\Responses;

class ValidateBasketResponse
{
    public bool $isValidated = false;
    public ?string $orderId = null;
    public ?int $orderStatus = null;
    public float $discount = 0.0;
    public float $finalCost = 0.0;
}