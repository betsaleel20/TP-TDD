<?php

namespace App\Application\Entities\Fruit;

use App\Application\ValueObjects\FruitReference;

interface FruitRepository
{
    public function saveUpdatedFruit(Fruit $fruit, int $position);
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

    /**
     * @param Fruit $fruit
     * @return void
     */
    public function updateFruitStatusToOccupied(Fruit $fruit):void;

}