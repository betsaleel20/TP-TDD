<?php

namespace App\Application\Enums;

enum OrderStatus : int
{
    case IS_CREATED = 1;
    case IS_PAYED = 2;
}