<?php

namespace App\Application\Entities\Fruit;

use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;

interface FruitRepository
{
    /**
     * @param FruitReference $fruitRef
     * @return Fruit|null
     */
    public function byReference(FruitReference $fruitRef): ?Fruit;

    /**
     * @param Fruit $fruit
     * @return void
     */
    public function updateFruitStatusToSold(Fruit $fruit):void;

    /**
     * @param FruitReference $reference
     * @return Fruit[]|null
     */
    public function allByReference(FruitReference $reference):?array ;
}