<?php

namespace App\Application\ValueObjects;

use App\Application\Entities\Order\Order;

class OrderElement
{

    public function __construct(
        private FruitReference $reference,
        private OrderedQuantity $orderedQuantity
    )
    {
    }

    public function reference(): FruitReference
    {
        return $this->reference;
    }


    public function quantity(): OrderedQuantity
    {
        return $this->orderedQuantity;
    }
    public function changeQuantity(OrderedQuantity $quantity):void
    {
        $this->orderedQuantity = $quantity;
    }

    public function changeReference(FruitReference $fruitReference):void
    {
        $this->reference = $fruitReference;
    }
}