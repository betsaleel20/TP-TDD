<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;

class ValidateFruitsReferencesService
{

    public function __construct(private FruitRepository $fruitRepository)
    {
    }

    public function execute(array $orderedElements)
    {
        $this->fruitRepository->byReference();

        $orderElements = $order->orderElements();
        $numberOfOrderedElements = count($orderElements);
        for($i = 0; $i < $numberOfOrderedElements; $i++){
            $this->validateFruitReferenceOrThrowInvalidFruitReferenceException($orderElements[$i]->reference());
        }
    }

}