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

    public function save(Fruit $fruit);

}