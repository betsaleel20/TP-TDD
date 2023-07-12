<?php

namespace App\Application\Enums;

enum FruitStatus : int
{
    case AVAILABLE = 1;
    case SOLD = 2;
    case OCCUPIED = 3;
}