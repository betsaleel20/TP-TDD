<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Quantity;

readonly class VerifyIfThereIsEnoughFruitInStockService
{

    private int $minimalStockQuantity;
    public function __construct(private FruitRepository $repository)
    {
        $this->minimalStockQuantity = 5;
    }

    /**
     * @param FruitReference $fruitReference
     * @param Quantity $neededQuantity
     * @return bool
     */
    public function execute(FruitReference $fruitReference, Quantity $neededQuantity):bool
    {
        $availableFruitsInConcernedReferences = $this->repository->allByReference($fruitReference);

        if(count($availableFruitsInConcernedReferences) < ($neededQuantity->value() + $this->minimalStockQuantity))
        {
            return false;
        }
        return true;
    }

}