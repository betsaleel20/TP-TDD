<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotAllowedQuantityToRemove;
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
use App\Application\ValueObjects\Quantity;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use mysql_xdevapi\Exception;

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
        $neededQuantity = new Quantity($command->neededQuantity);

        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);
        $existingBasket = $this->getBasketIfExistOrThrowNotFoundException($basketId);

        $basketElement = $this->buildBasketElement(
            action: BasketAction::in($command->action),
            fruitReference: $fruitRef,
            neededQuantity: $neededQuantity,
            existingBasket: $existingBasket
        );


        if( $basketElement?->quantity() ){
            $this->checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException( $fruitRef, $basketElement->quantity() );
        }

        $basket = Basket::create(
            newBasketElement: $basketElement,
            action:  BasketAction::in($command->action),
            existingBasket:  $existingBasket
        );


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
     * @param Quantity $neededQuantity
     * @return void
     */
    private function checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException(
        FruitReference $fruitReference,
        Quantity $neededQuantity
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

    /**
     * @param BasketAction $action
     * @param FruitReference $fruitReference
     * @param Quantity|null $neededQuantity
     * @param Basket|null $existingBasket
     * @return BasketElement
     * @throws NotFountElementInBasketException
     */
    private function buildBasketElement(
        BasketAction $action,
        FruitReference $fruitReference,
        ?Quantity $neededQuantity,
        ?Basket $existingBasket
    ):BasketElement
    {
        if( BasketAction::ADD_TO_BASKET->value === $action->value && !is_null($existingBasket) )
        {
            $neededQuantity = new Quantity(
                $existingBasket->findOneElementInBasket($fruitReference)?->quantity()->value() + $neededQuantity->value()
            );
        }
        if( BasketAction::REMOVE_FROM_BASKET->value === $action->value &&
            !is_null($existingBasket) &&
            !is_null($neededQuantity->value())
        )
        {
            $foundedElementInBasket = $existingBasket->findOneElementInBasket($fruitReference);
            if( !$foundedElementInBasket ){
                throw new NotFountElementInBasketException(
                    'Le fruit dont vous souhaitez diminuer la quantité n\'existe pas dans votre panier'
                );
            }

            $existingQuantity = $foundedElementInBasket->quantity()?->value();
            if($existingQuantity < $neededQuantity->value()){
                throw new NotAllowedQuantityToRemove(
                    'Vous n\'avez que '. $existingQuantity.' fruits dans votre panier.
                    Vous ne pouvez en retirer '.$neededQuantity->value());
            }
            $neededQuantity = new Quantity(
                $foundedElementInBasket->quantity()->value() - $neededQuantity->value()
            );
        }
        $basketElement = new BasketElement($fruitReference);
        if( $neededQuantity->value() )
        {
            $basketElement->changeQuantity($neededQuantity->value());
        }

        return $basketElement;
    }


}