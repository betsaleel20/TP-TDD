<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\NeededQuantity;

readonly class VerifyIfThereIsEnoughFruitInStockService
{

    private int $minimalStockQuantity;
    public function __construct(private FruitRepository $repository)
    {
        $this->minimalStockQuantity = 5;
    }

    /**
     * @param FruitReference $fruitReference
     * @param NeededQuantity $neededQuantity
     * @return bool
     */
    public function execute(FruitReference $fruitReference, NeededQuantity $neededQuantity):bool
    {
        $availableFruitsInConcernedReferences = $this->repository->allByReference($fruitReference);

        if(count($availableFruitsInConcernedReferences) < ($neededQuantity->value() + $this->minimalStockQuantity))
        {
            return false;
        }
        return true;
    }

}