<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\ValueObjects\OrderElement;

class ChangeFruitsStatusOfValidatedOrderToSoldService
{

    public function __construct(private FruitRepository $fruitRepository)
    {

    }

    /**
     * @param OrderElement $orderElement
     * @return void
     */
    public function execute(OrderElement $orderElement):void
    {
        $availableFruitsByReference = $this->fruitRepository->allByReference($orderElement->reference());
//        die(var_dump($availableFruitsByReference));
        if( empty($availableFruitsByReference) ){
            throw new NotFoundFruitReferenceException(
                'La Référence <'.$orderElement->reference()->value().'> que vous avez fournie est incorrecte'
            );
        }

        $neededQuantity = $orderElement->quantity()->value();
        if($neededQuantity > 0)
        {
            for($i = 0; $i < $neededQuantity; $i++)
            {
                $this->fruitRepository->updateFruitStatusToSold($availableFruitsByReference[$i]);
            }
        }
    }
}