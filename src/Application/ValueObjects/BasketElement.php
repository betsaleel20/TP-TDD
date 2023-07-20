<?php

namespace App\Application\ValueObjects;

class BasketElement
{
    public ?NeededQuantity $neededQuantity;


    public function __construct(
        private readonly FruitReference $reference
    )
    {
        $this->neededQuantity = null;
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function quantity(): NeededQuantity
    {
        return $this->neededQuantity;
    }

    public function changeQuantity(int $newQuantity):void
    {
        $this->neededQuantity = new NeededQuantity($newQuantity);
    }
}