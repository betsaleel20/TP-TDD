<?php

namespace App\Application\ValueObjects;

class BasketElement
{
    public Quantity $neededQuantity;


    public function __construct(
        private readonly FruitReference $reference
    )
    {
        $this->neededQuantity = new Quantity(0);
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }

    /**
     * @return Quantity
     */
    public function quantity(): Quantity
    {
        return $this->neededQuantity;
    }

    /**
     * @param int $quantity
     * @return void
     */
    public function changeQuantity(int $quantity):void
    {
        $this->neededQuantity = new Quantity($quantity);
    }

    public function increaseQuantity(int $quantity): void
    {
        $this->changeQuantity($this->neededQuantity->value() + $quantity);
    }

    public function decreaseQuantity(int $quantity): void
    {
        $this->changeQuantity($this->neededQuantity->value() - $quantity);
    }
}