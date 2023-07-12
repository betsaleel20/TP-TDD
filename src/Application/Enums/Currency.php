<?php

namespace App\Application\Enums;

enum Currency :int
{
    case XAF = 1;
    case XOF = 2;
    case DOLLAR = 3;
    case EURO = 4;

    public static function in(?int $currency): self
    {
        $self = self::tryFrom($currency);
        if (!$self) {
            throw new \InvalidArgumentException('Cette monnaie n\'est pas prise en charge par le système');
        }

        return $self;
    }
}