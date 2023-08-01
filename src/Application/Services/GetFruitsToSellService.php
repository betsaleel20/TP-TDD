<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Quantity;

readonly class GetFruitsToSellService
{

    /**
     * @param FruitRepository $fruitRepository
     */
    public function __construct( private FruitRepository $fruitRepository)
    {
    }

    /**
     * @param FruitReference $fruitRef
     * @param Quantity $numberOfFruitToRetrieve
     * @return Fruit[]
     */
    public function execute(FruitReference $fruitRef, Quantity $numberOfFruitToRetrieve ):array
    {
        $availableFruits =  $this->fruitRepository->allByReference($fruitRef);

        return array_slice(
            $availableFruits,
            0,
            $numberOfFruitToRetrieve->value()
        );
    }
}