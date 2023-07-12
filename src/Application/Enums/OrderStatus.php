<?php

namespace App\Application\Enums;

enum OrderStatus: int
{

    case PENDING = 1;
    case IS_DESTROYED = 2;
    case IS_VALIDATED = 3;
    case ONE_ELEMENT_REMOVED = 4;
}