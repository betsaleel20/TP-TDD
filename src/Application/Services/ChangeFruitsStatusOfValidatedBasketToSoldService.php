<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\ValueObjects\BasketElement;

readonly class ChangeFruitsStatusOfValidatedBasketToSoldService
{

    public function __construct(private FruitRepository $fruitRepository)
    {

    }

    /**
     * @param BasketElement $orderElement
     * @return void
     */
    public function execute(BasketElement $orderElement):void
    {
    }
}