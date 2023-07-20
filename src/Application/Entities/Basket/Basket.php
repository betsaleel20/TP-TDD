<?php

namespace App\Application\Entities\Basket;

use App\Application\Enums\Currency;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\BasketElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class Basket
{
    private ?PaymentMethod $paymentMethod = null;
    private ?Currency $currency = null;


    /**
     * @var BasketElement[]
     */
    private array $basketElements;
    private BasketStatus $status;

    /**
     * @param Id $id
     */
    private function __construct(
        readonly private Id $id
    )
    {
        $this->basketElements = [];
    }

    /**
     * @param BasketElement $newBasketElement
     * @param BasketAction $action
     * @param Basket|null $existingBasket
     * @return self
     * @throws NotFountElementInBasketException
     */
    public static function create(
        BasketElement $newBasketElement,
        BasketAction           $action,
        ?Basket       $existingBasket = null
    ): self
    {
        if(!$existingBasket){
            $self = new self(new Id(time()));
            $self->addElementToBasket($newBasketElement);
            $self->changeStatus(BasketStatus::IS_SAVED);
            return $self;
        }
        $existingBasket->updateBasket($newBasketElement, $action);
        return $existingBasket;
    }

    /**
     * @return Id|null
     */
    public function id(): ?Id
    {
        return $this->id;
    }

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function changePaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param Currency $currency
     */
    public function changeCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }



    /**
     * @param BasketElement $basketElement
     * @return void
     */
    public function addElementToBasket(BasketElement $basketElement): void
    {
        $this->basketElements[] = $basketElement;
    }


    /**
     * @return BasketElement[]
     */
    public function basketElements(): array
    {
        return $this->basketElements;
    }

    public function removeElementFromBasket(FruitReference $reference): void
    {
        $this->basketElements = array_values(array_filter(
            $this->basketElements,
            fn(BasketElement $e) => $e->reference()->referenceValue() !== $reference->referenceValue()
        ));

        if (count($this->basketElements) === 0) {
            $this->changeStatus(BasketStatus::IS_DESTROYED);
        }
    }

    /**
     * @throws NotFountElementInBasketException
     */
    public function updateBasket( BasketElement $basketElement, BasketAction  $action ): void
    {
        $state = $this->checkElementExistence($basketElement->reference());
        if( !$state && $action !== BasketAction::ADD_TO_BASKET ){
            throw new NotFountElementInBasketException('L\'element que vous souhaitez supprimer n\'existe pas');
        }
        if ( $action === BasketAction::ADD_TO_BASKET ) {
            if($state){
                $this->updateAddedElementQuantity($basketElement);
                return;
            }
            $this->addElementToBasket($basketElement);
            return;
        }

        if( $action === BasketAction::REMOVE_FROM_BASKET && !is_null( $basketElement->quantity()->value() ) ){
            $this->updateAddedElementQuantity($basketElement, false);
            return;
        }
        $this->removeElementFromBasket( $basketElement->reference() );
    }

    public function status(): BasketStatus
    {
        return $this->status;
    }

    public function changeStatus(BasketStatus $status): void
    {
        $this->status = $status;
    }

    public function checkElementExistence(FruitReference $reference): bool
    {
        $foundElement = array_values(array_filter(
            $this->basketElements,
            fn(BasketElement $oe)=>$oe->reference()->referenceValue() === $reference->referenceValue()
        ));
        return count($foundElement) > 0;
    }

    /**
     * @param BasketElement $incomingElement
     * @param bool $increaseQuantity
     * @return void
     * @throws NotFountElementInBasketException
     */
    public function updateAddedElementQuantity(BasketElement $incomingElement, bool $increaseQuantity = true):void
    {
        $existingElementInBasket = $this->findOneElementInBasket($incomingElement->reference());
        if( !$existingElementInBasket ){
            throw new NotFountElementInBasketException(
                'L\'element que vous recherchez n\'existe pas dans ce panier'
            );
        }
        $incomingQuantity = $incomingElement->quantity()->value();

        $incomingElement->changeQuantity(
            $existingElementInBasket->quantity()->value() + $incomingQuantity
        );
        if(!$increaseQuantity){
            $incomingElement->changeQuantity(
                $existingElementInBasket->quantity()->value() - $incomingQuantity
            );
        }
        $this->removeFromBasket($existingElementInBasket);
        $this->addElementToBasket($incomingElement);
    }

    /**
     * @return PaymentMethod
     */
    public function paymentMethod():PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @return Currency
     */
    public function currency():Currency
    {
        return $this->currency;
    }

    /**
     * @return BasketElement|null
     */
    public function lastElement(): ?BasketElement
    {
        $numberOfElementsInBasket = count($this->basketElements());
        return $this->basketElements()[$numberOfElementsInBasket - 1 ];
    }

    /**
     * @param BasketElement $elementToRemove
     * @return void
     */
    public function removeFromBasket(BasketElement $elementToRemove): void
    {
        $this->basketElements = array_values(array_filter(
            $this->basketElements(),
            fn(BasketElement $be) => $be->reference()->referenceValue() !== $elementToRemove->reference()->referenceValue()
        ));
    }

    /**
     * @param FruitReference $elementReference
     * @return BasketElement|null
     */
    public function findOneElementInBasket(FruitReference $elementReference): ?BasketElement
    {
        $foundElement = array_values(array_filter(
            $this->basketElements(),
            fn(BasketElement $be) => $be->reference()->referenceValue() === $elementReference->referenceValue()
        ));
        return count($foundElement) > 0 ? $foundElement[0] : null ;
    }

}