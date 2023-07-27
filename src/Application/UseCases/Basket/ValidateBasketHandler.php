<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\FruitStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\EmptyBasketException;
use App\Application\Exceptions\IncorrectEnteredAmountException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\Services\GetFruitsToSoldService;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\Id;

readonly class ValidateBasketHandler
{

    /**
     * @param BasketRepository $basketRepository
     * @param FruitRepository $fruitRepository
     * @param GetFruitsToSoldService $fruitsToSoldInMemory
     */
    public function __construct(
        private BasketRepository                                 $basketRepository,
        private FruitRepository                                  $fruitRepository,
        private GetFruitsToSoldService $fruitsToSoldInMemory
    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();

        $basket = $this->basketRepository->byId(new Id( $command->basketId ));
        if(!$basket){
            throw new NotFoundBasketException('Identifiant incorrect: le panier que vous souhaitez valider n\'existe pas.');
        }

        $basketElements = $basket->basketElements();
        if(empty($basketElements)){
            throw new EmptyBasketException("Le panier que vous spuhaitez valider ne contient aucun produit");
        }
        $this->verifyIfFruitReferenceStillExistsOrThrowNotFoundFruitReferenceException( $basketElements );
        $this->checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException( $basketElements );

        $this->applyDiscountOnBasketIfQualified($basket);

        $fruitsToSold = $this->getFruitsToSold($basketElements);
        $order = Order::create(
            fruitsToSold: $fruitsToSold,
            paymentMethod:  PaymentMethod::in($command->paymentMethod),
            currency: Currency::in($command->currency)
        );

        $basket->makeBasketEmpty();
        $basket->changeStatus(BasketStatus::IS_DESTROYED);
        $response->orderId = $order->id()->value();
        $response->isValidated = true;
        return $response;
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

    /**
     * @param array $basketElements
     * @return Fruit[]
     */
    private function getFruitsToSold(array $basketElements): array
    {
        $items = count($basketElements);
        $fruits = [];
        for($i = 0; $i < $items; $i++)
        {
            $fruitsByReference = $this->fruitsToSoldInMemory->execute(
                $basketElements[$i]->reference(),
                $basketElements[$i]->quantity()
            );
            $fruits = array_merge($fruits, $fruitsByReference);
        }
        return $fruits;
    }

    private function applyDiscountOnBasketIfQualified(Basket $basket)
    {

    }

}