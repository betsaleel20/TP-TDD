<?php

namespace App\Application\Entities\Basket;

use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;

interface BasketRepository
{
    public function save(Basket $basket): void;

    public function byId(Id $basketId): ?Basket;

    /**
     * @return Basket[]
     */
    public function allBaskets():array;
}