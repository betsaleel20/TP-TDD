<?php

namespace App\Application\Responses;

class ValidateOrderResponse
{
    public bool $isPending = false;
    public bool $isValidated = false;
    public bool $isDestroyed = false;
    public ?string $orderId = null;
    public ?int $paymentMethod = null;
    public ?int $currency = null;

}