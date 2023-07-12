<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\ValueObjects\OrderElement;

class VerifyIfThereIsEnoughFruitInStockService
{

    public function __construct(private FruitRepository $repository)
    {
    }

    public function execute(OrderElement $orderElement):void
    {
        $fruitInStock = $this->repository->fruits();
        $availableFruitsWithOrderedReference = array_values(array_filter(
            $fruitInStock,
            fn(Fruit $f)=>$f->reference()->value() === $orderElement->reference()->value()
        ));

        $minimalStockQuantity = 5;
        if(count($availableFruitsWithOrderedReference) < ($orderElement->quantity()->value() + $minimalStockQuantity)){
            throw new UnavailableFruitQuantityException('La quatité de fruit pour la référence <'.$orderElement->reference()->value().'> est insuffisante');
        }
    }

}