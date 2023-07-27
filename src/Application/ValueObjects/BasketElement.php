<?php

namespace App\Application\ValueObjects;

class BasketElement
{
    public Quantity $quantity;

    public function __construct( private readonly FruitReference $reference )
    {
        $this->quantity = new Quantity(0);
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
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return void
     */
    public function changeQuantity(int $quantity):void
    {
        $this->quantity = new Quantity($quantity);
    }

    public function increaseQuantity(int $quantity): void
    {
        $this->changeQuantity($this->quantity->value() + $quantity);
    }

    public function decreaseQuantity(int $quantity): void
    {
        $this->changeQuantity($this->quantity->value() - $quantity);
    }

    /**
     * @return float
     */
    public function calculateAmount():float
    {
        return $this->reference()->price() * $this->quantity()->value();
    }
}