<?php

namespace App\Application\Enums;

enum PaymentMethod :int
{
    case MASTERCARD = 1;
    case VISA = 2;

    /**
     * @param int|null $paymentMethod
     * @return self
     */
    public static function in(?int $paymentMethod): self
    {
        $self = self::tryFrom($paymentMethod);
        if (!$self) {
            throw new \InvalidArgumentException('Ce mode de paiement n\'est pas pris en charge par le système');
        }
        return $self;
    }

}