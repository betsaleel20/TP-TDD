<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\BasketAction;
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

readonly class SaveBasketHandler
{
    public function __construct(
        private BasketRepository                         $basketRepository,
        private GetFruitByReferenceService               $verifyIfFruitReferenceExistsOrThrowNotFoundException,
        private VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStock
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
        $neededQuantity = new Quantity($command->neededQuantity ?? 0 );

        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);
        $existingBasket = $this->getBasketIfExistOrThrowNotFoundException($basketId);

        $basketElement = new BasketElement($fruitRef);
        $basketElement->changeQuantity($neededQuantity->value());

        $action = BasketAction::in($command->action);

        $this->checkIfQuantityStillAvailableOrThrowException( $basketElement, $action, $existingBasket );

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
     * @param BasketElement $basketElement
     * @param BasketAction $action
     * @param Basket|null $existingBasket
     * @return void
     */
    private function checkIfQuantityStillAvailableOrThrowException
    (
        BasketElement $basketElement,
        BasketAction $action,
        ?Basket $existingBasket
    ): void
    {
        $fruitReference = $basketElement->reference();
        $quantity = $basketElement->quantity();

        if( !$existingBasket && $action === BasketAction::ADD_TO_BASKET ){
            $this->verifyIfThereIsEnoughFruitsInStockOrThrowUnavailableFruitQuantityException($fruitReference, $quantity);
            return;
        }

        if(!$existingBasket){
            return;
        }

        $foundedElementInBasket = $existingBasket->findOneElement($fruitReference);

        if( $action === BasketAction::ADD_TO_BASKET )
        {
            $quantity = new Quantity(
                $foundedElementInBasket?->quantity()->value() + $quantity->value()
            );
            $this->verifyIfThereIsEnoughFruitsInStockOrThrowUnavailableFruitQuantityException($fruitReference, $quantity);
            return;
        }

        if( BasketAction::DECREASE_QUANTITY->value === $action->value && $foundedElementInBasket )
        {
            $quantityInBasket = $foundedElementInBasket->quantity()->value();
            $quantityInBasket >= $quantity->value() ? : throw new NotAllowedQuantityToRemove(
                'Vous ne pouvez pas retirer plus de <'.$quantityInBasket.'> fruits de votre panier'
            );

            $quantity = new Quantity( $quantityInBasket - $quantity->value() );
            $this->verifyIfThereIsEnoughFruitsInStockOrThrowUnavailableFruitQuantityException($fruitReference, $quantity);
        }
    }

    /**
     * @param FruitReference $fruitReference
     * @param Quantity $neededQuantity
     * @return void
     */
    public function verifyIfThereIsEnoughFruitsInStockOrThrowUnavailableFruitQuantityException(
        FruitReference $fruitReference,
        Quantity $neededQuantity
    ): void
    {
        $state = $this->verifyIfThereIsEnoughFruitInStock->execute( $fruitReference, $neededQuantity );

        if (!$state) {
            throw new UnavailableFruitQuantityException(
                'Vous ne pouvez pas commander jusqu\'à ' . $neededQuantity->value() . ' fruits de la reference <' .
                $fruitReference->referenceValue() . '>'
            );
        }
    }


}