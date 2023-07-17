<?php

namespace App\Application\Services;

use App\Application\Entities\Fruit\FruitRepository;
use App\Application\ValueObjects\FruitReference;

readonly class VerifyIfFruitReferenceExistService
{
    public function __construct(private FruitRepository $fruitRepository)
    {
    }

    public function execute(FruitReference $reference):bool
    {
        if($this->fruitRepository->byReference($reference)){
            return true;
        }
        return false;
    }
}