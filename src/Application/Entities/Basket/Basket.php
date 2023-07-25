<?php

namespace App\Application\Entities\Basket;

use App\Application\Enums\Currency;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\Quantity;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class Basket
{
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
     * @throws NotFoundBasketException
     */
    public static function create(
        BasketElement $newBasketElement,
        BasketAction           $action,
        ?Basket       $existingBasket = null
    ): self
    {
        if($action === BasketAction::REMOVE_FROM_BASKET && !$existingBasket){
            throw new NotFoundBasketException("Vous ne pouvez pas retirer un element dans un panier inexistant");
        }
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
    }

    /**
     * @param BasketElement $basketElement
     * @param BasketAction $action
     * @return void
     * @throws NotFountElementInBasketException
     */
    public function updateBasket( BasketElement $basketElement, BasketAction  $action ): void
    {
        $state = $this->checkElementExistence($basketElement->reference());
        if(!$state){
            switch ($action){
                case BasketAction::REMOVE_FROM_BASKET:
                    throw new NotFountElementInBasketException('L\'element que vous souhaitez supprimer n\'existe pas');
                case BasketAction::ADD_TO_BASKET:
                    $this->addElementToBasket($basketElement);
                    break;
            }
        }

        if($state){
            $this->removeElementFromBasket($basketElement->reference());
            if($action === BasketAction::ADD_TO_BASKET ||
                $action === BasketAction::REMOVE_FROM_BASKET && $basketElement->quantity()?->value() ){
                $this->addElementToBasket($basketElement);
            }
        }
        if (count($this->basketElements) === 0) {
            $this->changeStatus(BasketStatus::IS_DESTROYED);
        }

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
        $this->removeElementFromBasket($existingElementInBasket->reference());
        $this->addElementToBasket($incomingElement);
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

    /**
     * @return void
     */
    public function makeBasketEmpty():void
    {
        $this->basketElements = [];
    }

}