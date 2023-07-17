<?php

namespace App\Application\ValueObjects;

class BasketElement
{

    public function __construct(
        private readonly FruitReference $reference,
        private NeededQuantity $orderedQuantity
    )
    {
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function quantity(): NeededQuantity
    {
        return $this->orderedQuantity;
    }
    public function changeQuantity(NeededQuantity $quantity):void
    {
        $this->orderedQuantity = $quantity;
    }
}