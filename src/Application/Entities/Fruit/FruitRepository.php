<?php

namespace App\Application\Entities\Fruit;

use App\Application\ValueObjects\FruitReference;

interface FruitRepository
{
    /**
     * @param FruitReference $fruitRef
     * @return Fruit|null
     */
    public function byReference(FruitReference $fruitRef): ?Fruit;

    /**
     * @param FruitReference $reference
     * @return Fruit[]|null
     */
    public function allByReference(FruitReference $reference):?array ;

    /**
     * @param Fruit $fruit
     */
    public function save(Fruit $fruit):void;

    /**
     * @return Fruit[]
     */
    public function all(): array;

    /**
     * @param Fruit[] $soldFruits
     * @return void
     */
    public function saveMany(array $soldFruits):void;

}