<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateOrderResponse;
use App\Application\Services\ChangeFruitsStatusOfValidatedBasketToSoldService;
use App\Application\Services\VerifyIfFruitReferenceExistService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\BasketElement;

readonly class ValidateBasketHandler
{

    /**
     * @param BasketRepository $basketRepository
     * @param FruitRepository $fruitRepository
     */
    public function __construct(
        private BasketRepository                                 $basketRepository,
        private FruitRepository                                  $fruitRepository
    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateBasketCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();

        $basket = $this->basketRepository->byId(new Id($command->id));
        if(!$basket){
            throw new NotFoundBasketException('Identifiant incorrect: le panier que vous souhaitez valider n\'existe pas.');
        }

        $basket->changePaymentMethod(
            $this->getPaymentMethodOrThrowInvalidArgumentsException($command->paymentMethod)
        );
        $basket->changeCurrency(
            $this->getCurrencyOrThrowInvalidArgumentsException($command->currency)
        );

        $this->verifyIfFruitReferenceStillExistsOrThrowNotFoundFruitReferenceException($basket->basketElements());

        $this->checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException( $basket->basketElements() );

        $this->soldFruits($basket->basketElements());

        $basket->changeStatus(BasketStatus::IS_VALIDATED);

        $response->orderId = $basket->id()->value();
        $response->isValidated = true;
        return $response;
    }

    /**
     * @param int $paymentMethod
     * @return PaymentMethod
     */
    private function getPaymentMethodOrThrowInvalidArgumentsException(int $paymentMethod): PaymentMethod
    {
        return PaymentMethod::in($paymentMethod);
    }

    private function getCurrencyOrThrowInvalidArgumentsException(int $currency):Currency
    {
        return Currency::in($currency);
    }

    /**
     * @param BasketElement[] $elementsAddedToBasket
     * @return void
     * @throws UnavailableFruitQuantityException
     */
    private function checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException(array $elementsAddedToBasket):void
    {
        $messages = null;
        $numberOfSelectedElements = count($elementsAddedToBasket);
        for($i = 0; $i < $numberOfSelectedElements; $i++){
            $availableFruits = $this->fruitRepository->allByReference($elementsAddedToBasket[$i]->reference());
            $minimalStockQuantity = 5;
            if(count($availableFruits) < ($elementsAddedToBasket[$i]->quantity()->value() + $minimalStockQuantity))
            {
                $messages .= '[La quatité de fruit disponibles pour la référence <'.$elementsAddedToBasket[$i]->reference()->value().'> est insuffisante] | ';
            }
        }
        if($messages){
            throw new UnavailableFruitQuantityException($messages);
        }
    }

    /**
     * @param BasketElement[] $elementsAddedToBasket
     * @return void
     */
    private function verifyIfFruitReferenceStillExistsOrThrowNotFoundFruitReferenceException(array $elementsAddedToBasket):void
    {
        $messages = null;
        $numberOfOrderedElements = count($elementsAddedToBasket);
        for($i = 0; $i < $numberOfOrderedElements; $i++){
            $state = $this->fruitRepository->allByReference($elementsAddedToBasket[$i]->reference());
            if(!$state)
            {
                $messages .= '[Les produits de la référence <'.$elementsAddedToBasket[$i]->reference()->value().'> n\'existe plus dans le systeme ] | ';
            }
        }
        if($messages){
            throw new NotFoundFruitReferenceException($messages);
        }
    }

    /**
     * @param BasketElement[] $basketElements
     * @return void
     */
    private function soldFruits(array $basketElements): void
    {
        foreach ($basketElements as $basketElement) {
            $availableFruitsByReference = $this->fruitRepository->allByReference($basketElement->reference());
            $neededQuantity = $basketElement->quantity()->value();
            for($i = 0; $i < $neededQuantity; $i++){
                $this->fruitRepository->updateFruitStatusToOccupied($availableFruitsByReference[$i]);
                $this->fruitRepository->saveUpdatedFruit($availableFruitsByReference[$i], $i);
            }

            for($i = 0; $i < $neededQuantity; $i++)
            {
                $this->fruitRepository->updateFruitStatusToSold($availableFruitsByReference[$i]);
                $this->fruitRepository->saveUpdatedFruit($availableFruitsByReference[$i], $i);
            }
        }

    }

}