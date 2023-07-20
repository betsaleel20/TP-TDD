<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\FruitStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\IncorrectEnteredAmountException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\Id;

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
    public function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();

        $basket = $this->basketRepository->byId(new Id( $command->id ));
        if(!$basket){
            throw new NotFoundBasketException('Identifiant incorrect: le panier que vous souhaitez valider n\'existe pas.');
        }

        $basketElements = $basket->basketElements();
        $this->verifyIfFruitReferenceStillExistsOrThrowNotFoundFruitReferenceException( $basketElements );
        $this->checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException( $basketElements );
        $basket->changePaymentMethod(
            $this->getPaymentMethodOrThrowInvalidArgumentsException( $command->paymentMethod )
        );
        $basket->changeCurrency(
            $this->getCurrencyOrThrowInvalidArgumentsException( $command->currency )
        );

        $order = Order::create( $basketElements );

        $basket->changeStatus(BasketStatus::IS_VALIDATED);

        //ToDO:: Create the order

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
                $messages .= '[La quatité de fruit disponibles pour la référence <'.$elementsAddedToBasket[$i]->reference()->referenceValue().'> est insuffisante] | ';
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
        $numberOfSelectedElements = count($elementsAddedToBasket);
        for($i = 0; $i < $numberOfSelectedElements; $i++){
            $state = $this->fruitRepository->allByReference($elementsAddedToBasket[$i]->reference());
            if(!$state)
            {
                $messages .= '[Les produits de la référence <'.$elementsAddedToBasket[$i]->reference()->referenceValue().'> n\'existe plus dans le systeme ] | ';
            }
        }
        if($messages){
            throw new NotFoundFruitReferenceException($messages);
        }
    }

}