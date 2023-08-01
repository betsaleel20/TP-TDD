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
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\Services\GetFruitsToSellService;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\Quantity;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;

readonly class ValidateBasketHandler
{

    private OrderRepository $orderRepository;

    /**
     * @param BasketRepository $basketRepository
     * @param FruitRepository $fruitRepository
     * @param GetFruitsToSellService $fruitsToSaleInMemory
     */
    public function __construct(
        private BasketRepository                                 $basketRepository,
        private FruitRepository                                  $fruitRepository,
        private GetFruitsToSellService $fruitsToSaleInMemory
    )
    {
        $this->orderRepository = new InMemoryOrderRepository();
    }

    /**
     * @throws NotFoundBasketException
     */
    public function handle(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $response = new ValidateBasketResponse();

        $basket = $this->retrieveBasketOrThrowNotFoundException(new Id( $command->basketId ));
        $basketElements = $this->getBasketElementsOrThrowEmptyBasketException($basket);
        $fruitsForSale = $this->getFruitsForSaleOrThrowExceptions($basketElements);

        $this->markFruitsAsSold($fruitsForSale);
        $order = Order::create(
            fruitsToSale: $fruitsForSale,
            paymentMethod:  PaymentMethod::in($command->paymentMethod),
            currency: Currency::in($command->currency)
        );
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
     * @param array $availableFruitsInMemory
     * @return void
     */
    private function checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException(
        array $elementsAddedToBasket,
        array $availableFruitsInMemory
    ):void
    {
        $messages = null;
        $references = array_keys($elementsAddedToBasket);
        $minimalStockQuantity = 5;
        foreach ($references as $reference){
            $availableFruits = array_filter(
                $availableFruitsInMemory,
                fn(Fruit $f)=>$f->reference()->referenceValue() === $reference
            );

            if(count($availableFruits) < ($elementsAddedToBasket[$reference]['quantity'] + $minimalStockQuantity))
            {
                $messages .= '[La quatité de fruit disponibles pour la référence <'.$reference.'> est insuffisante] | ';
            }
        }
        if($messages){
            throw new UnavailableFruitQuantityException($messages);
        }
    }

    /**
     * @param array $givenReferences
     * @param array $existingReferencesInMemory
     * @return void
     */
    private function verifyIfReferenceStillExistOrThrowNotFoundFruitReferenceException(
        array $givenReferences,
        array $existingReferencesInMemory
    ):void
    {
        $messages = null;
        foreach ($givenReferences as $givenReference){
            $state = in_array($givenReference, $existingReferencesInMemory);
            if(!$state)
            {
                $messages .= '[Les produits de la référence <'.$givenReference.'> n\'existe plus dans le systeme ] | ';
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
    private function getFruitsForSaleOrThrowExceptions(array $basketElements): array
    {
        $availableFruitsInMemory = $this->fruitRepository->all();
        if(count($availableFruitsInMemory) === 0) {
            throw new UnavailableFruitQuantityException('Il n\'existe plus de fruits en vente sur ce site. Veuillez re-ésssayer plus tard');
        }

        $referencesInMemory = $this->getAllReferences($availableFruitsInMemory);

        $givenReferences = array_keys($basketElements);
        $this->verifyIfReferenceStillExistOrThrowNotFoundFruitReferenceException(
            $givenReferences,
            $referencesInMemory
        );
        $this->checkIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException( $basketElements, $availableFruitsInMemory );

        $fruits = [];
        foreach ($givenReferences as $givenReference)
        {
            $fruitsByReference = $this->fruitsToSaleInMemory->execute(
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
     * @param Fruit[] $availableFruitsInMemory
     * @return array
     */
    private function getAllReferences(array $availableFruitsInMemory):array
    {
        $fruitOfEachReference = $this->getOneFruitOfEachReference($availableFruitsInMemory);
        return $this->retrieveReferences($fruitOfEachReference);
    }

    /**
     * @param Fruit[] $availableFruitsInMemory
     * @return array
     */
    private function getOneFruitOfEachReference(array $availableFruitsInMemory):array
    {
        $firstIndex = array_key_first($availableFruitsInMemory);
        $fruitOfEachReference = array_values(array_filter(
            $availableFruitsInMemory,
            fn(Fruit $f) => $f->reference()->referenceValue() !== $availableFruitsInMemory[$firstIndex]->reference()->referenceValue()
        ));
        $fruitOfEachReference[] = $availableFruitsInMemory[$firstIndex];
        return $fruitOfEachReference;
    }

    /**
     * @param Fruit[] $fruitOfEachReference
     * @return array
     */
    private function retrieveReferences(array $fruitOfEachReference):array
    {
        $references = [];
        foreach ($fruitOfEachReference as $fruit) {
            $references[] = $fruit->reference()->referenceValue();
        }
        return $references;
    }

    /**
     * @param Fruit[] $soldFruits
     * @return void
     */
    private function markFruitsAsSold(array $soldFruits):void
    {
        foreach ($soldFruits as $soldFruit) {
            $soldFruit->changeStatus(FruitStatus::SOLD);
            $this->fruitRepository->save($soldFruit);
        }
    }


}