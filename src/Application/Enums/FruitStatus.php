<?php

namespace App\Application\Enums;

use App\Application\Exceptions\InvalidStatusException;

enum FruitStatus : int
{
    case AVAILABLE = 1;
    case SOLD = 2;

    public static function in(?int $action): self
    {
        $self = self::tryFrom($action);
        if (!$self) {
            throw new InvalidStatusException('Ce status n\'est pas pris en compte par le systeme');
        }

        return $self;
    }
}