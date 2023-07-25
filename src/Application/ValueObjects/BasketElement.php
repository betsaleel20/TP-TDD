<?php

namespace App\Application\ValueObjects;

class BasketElement
{
    public ?Quantity $neededQuantity;


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

    /**
     * @return Quantity|null
     */
    public function quantity(): ?Quantity
    {
        return $this->neededQuantity;
    }

    public function changeQuantity(int $newQuantity):void
    {
        $this->neededQuantity = new Quantity($newQuantity);
    }
}