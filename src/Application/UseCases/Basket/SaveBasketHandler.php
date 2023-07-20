<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\SaveBasketResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\NeededQuantity;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

readonly class SaveBasketHandler
{



    public function __construct(
        private BasketRepository                         $basketRepository,
        private GetFruitByReferenceService               $verifyIfFruitReferenceExistsOrThrowNotFoundException,
        private VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException
    )
    {
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundFruitReferenceException
     * @throws NotFountElementInBasketException
     */
    public function handle(SaveBasketCommand $command): SaveBasketResponse
    {
        $response = new SaveBasketResponse();

        $basketId = $command->basketId ? new Id($command->basketId) : null;
        $fruitRef = new FruitReference(reference: $command->fruitRef);
        $neededQuantity = new NeededQuantity($command->neededQuantity);

        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);
        $this->checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException( $fruitRef, $neededQuantity );
        $existingBasket = $this->getBasketIfExistOrThrowNotFoundException($basketId);

        $basketElement = new BasketElement(
            reference: $fruitRef
        );
        $basketElement->neededQuantity = $neededQuantity;

        $basket = Basket::create(
            newBasketElement: $basketElement,
            action:  BasketAction::in($command->action),
            existingBasket:  $existingBasket
        );

        if($basket->status()->value !== BasketStatus::IS_DESTROYED->value){
            $newNeededQuantity = $basket->lastElement()->quantity();
            $this->checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException( $fruitRef, $newNeededQuantity );
        }

        $this->basketRepository->save( $basket );

        $response->isSaved = true;
        $response->basketId = $basket->id()->value();
        $response->basketStatus = $basket->status()->value;

        return $response;
    }

    /**
     * @param Id|null $basketId
     * @return Basket|null
     * @throws NotFoundBasketException
     */
    private function getBasketIfExistOrThrowNotFoundException(?Id $basketId): ?Basket
    {
        if(!$basketId){
            return null;
        }

        $basket = $this->basketRepository->byId($basketId);
        if (!$basket) {
            throw new NotFoundBasketException("Ce panier n'existe pas !");
        }

        return $basket;
    }

    /**
     * @param FruitReference $fruitReference
     * @param NeededQuantity $neededQuantity
     * @return void
     */
    private function checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException(
        FruitReference $fruitReference,
        NeededQuantity $neededQuantity
    ): void
    {
        $state = $this->verifyIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException->execute(
            $fruitReference,
            $neededQuantity
        );
        if(!$state)
        {
            throw new UnavailableFruitQuantityException(
                'Vous ne pouvez pas commander jusqu\'à '.$neededQuantity->value().' fruits de la reference <'.
                $fruitReference->referenceValue().'>'
            );
        }
    }


}