<?php

namespace App\Application\UseCases\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\FruitStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\EmptyBasketException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\FruitsOutOfStockException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\Services\GetFruitsToSaleService;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\Quantity;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;

readonly class ValidateBasketHandler
{
    /**
     * @param BasketRepository $basketRepository
     * @param FruitRepository $fruitRepository
     * @param GetFruitsToSaleService $getFruitsToSaleInMemory
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        private BasketRepository                                 $basketRepository,
        private FruitRepository                                  $fruitRepository,
        private OrderRepository $orderRepository,
        private GetFruitsToSaleService $getFruitsToSaleInMemory,
    )
    {
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();

        $basket = $this->retrieveBasketOrThrowNotFoundException(new Id( $command->basketId ));
        $basketElements = $this->getBasketElementsOrThrowEmptyBasketException($basket);

        $this->verifyIfReferenceStillExistOrThrowNotFoundFruitReferenceException( $basketElements );
        $this->checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException( $basketElements );
        $fruitsForSale = $this->getFruitsForSaleOrThrowFruitsOutOfStockException($basketElements);

        $order = Order::create(
            fruitsToSale: $fruitsForSale,
            paymentMethod:  PaymentMethod::in($command->paymentMethod),
            currency: Currency::in($command->currency)
        );
        $this->orderRepository->save($order);
        $this->markFruitsAsSold($fruitsForSale);
        $this->applyDiscountOnTheFirstElementOfBasket($order, $basketElements);
        $this->applyDiscountOnTheBasket($order, $basket);

        $basket->makeBasketEmpty();
        $basket->changeStatus(BasketStatus::IS_DESTROYED);
        $response->finalCost = $order->totalCost();
        $response->discount = $order->discount();
        $response->orderId = $order->id()->value();
        $response->orderStatus = $order->status()->value;
        $response->isValidated = true;
        return $response;
    }

    /**
     * @param BasketElement[] $elementsAddedToBasket
     * @return void
     */
    private function checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException(
        array $elementsAddedToBasket
    ):void
    {
        $messages = null;
        $availableFruitsInMemory = $this->fruitRepository->all();
        $givenReferences = array_keys($elementsAddedToBasket);
        $minimalStockQuantity = 5;

        foreach ($givenReferences as $reference){
            $availableFruits = array_filter(
                $availableFruitsInMemory,
                fn(Fruit $f)=>$f->reference()->referenceValue() === $reference
            );

            if(count($availableFruits) < ($elementsAddedToBasket[$reference]['quantity'] + $minimalStockQuantity))
            {
                $messages .= '[La quatité de fruit disponibles pour la référence <'.$reference.'> est insuffisante] | ';
            }
        }

        is_null($messages) ? : throw new FruitsOutOfStockException($messages);
    }

    /**
     * @param array $basketElements
     * @param array $existingReferencesInMemory
     * @return void
     */
    private function verifyIfReferenceStillExistOrThrowNotFoundFruitReferenceException( array $basketElements ):void
    {
        $messages = null;
        $givenReferences = array_keys($basketElements);
        foreach ($givenReferences as $givenReference){
            $state = $this->fruitRepository->byReference(new FruitReference($givenReference));
            !is_null($state) ? : $messages .= '[Les produits de la référence <'.$givenReference.'> sont indisponibles] | ';
        }
        is_null($messages) ? : throw new NotFoundFruitReferenceException($messages);
    }

    /**
     * @param BasketElement[] $basketElements
     * @return Fruit[]
     */
    private function getFruitsForSaleOrThrowFruitsOutOfStockException(array $basketElements): array
    {
        $availableFruitsInMemory = $this->fruitRepository->all();
        if(count($availableFruitsInMemory) === 0) {
            throw new FruitsOutOfStockException('Les fruits sont en rupture de stock dans nos magasins');
        }

        $givenReferences = array_keys($basketElements);
        $fruits = [];
        foreach ($givenReferences as $givenReference)
        {
            $fruitsByReference = $this->getFruitsToSaleInMemory->execute(
               new FruitReference($givenReference),
               new Quantity( $basketElements[$givenReference]['quantity'] )
            );
            $fruits = array_merge($fruits, $fruitsByReference);
        }
        return $fruits;
    }

    /**
     * @param Order $order
     * @param BasketElement[] $basketElements
     * @return void
     */
    private function applyDiscountOnTheFirstElementOfBasket(Order $order, array $basketElements): void
    {
        $reference = array_key_first($basketElements);
        if($basketElements[$reference]['quantity'] < 10){
            return ;
        }
        $order->discountOnFirstElement( $basketElements[$reference]['quantity'], $order->soldFruits()[0]->reference()->price() );
    }

    /**
     * @param Order $order
     * @param Basket $basket
     * @return void
     */
    private function applyDiscountOnTheBasket(Order $order, Basket $basket):void
    {
        $basketElements = $basket->basketElements();
        $numberOfFruits = 0;
        $tenPercent = 10;
        $fifteenPerCent = 15;
        foreach ($basketElements as $basketElement){
            $numberOfFruits += $basketElement['quantity'];
        }

        if($numberOfFruits <= 10){
            return ;
        }

        if( $numberOfFruits < 20 ){
            $order->applyDiscount($tenPercent);
            return ;
        }

        $order->applyDiscount($fifteenPerCent);
    }

    /**
     * @param Id $basketId
     * @return Basket|null
     * @throws NotFoundBasketException
     */
    private function retrieveBasketOrThrowNotFoundException(Id $basketId):?Basket
    {
        $basket = $this->basketRepository->byId( $basketId );
        if (!$basket) {
            throw new NotFoundBasketException('Identifiant incorrect: le panier que vous souhaitez valider n\'existe pas.');
        }
        return $basket;
    }

    /**
     * @param Basket $basket
     * @return BasketElement[]
     */
    private function getBasketElementsOrThrowEmptyBasketException( Basket $basket ): array
    {
        $basketElements = $basket->basketElements();
        if (empty($basketElements)) {
            throw new EmptyBasketException("Le panier que vous spuhaitez valider ne contient aucun produit");
        }
        return $basketElements;
    }

    /**
     * @param Fruit[] $soldFruits
     * @return void
     */
    private function markFruitsAsSold(array $soldFruits):void
    {
        foreach ($soldFruits as $index => $soldFruit) {
            $soldFruit->changeStatus(FruitStatus::SOLD);
            $soldFruits[$index] = $soldFruit;
        }
        $this->fruitRepository->saveMany($soldFruits);
    }


}