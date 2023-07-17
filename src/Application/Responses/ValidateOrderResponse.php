<?php

namespace App\Application\Responses;

class ValidateOrderResponse
{
    public bool $isValidated = false;
    public ?string $orderId = null;
}