<?php

namespace App\Application\Entities\Basket;

use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\NotAllowedQuantityToRemove;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\Quantity;

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
        if($action !== BasketAction::ADD_TO_BASKET && !$existingBasket){
            throw new NotFoundBasketException('Vous ne pouvez pas manipuler les elements d\'un panier inexistant!');
        }
        if(!$existingBasket){
            $basket = new self(new Id(time()));
            $basket->addElementToBasket($newBasketElement);
            $basket->changeStatus(BasketStatus::IS_SAVED);
            return $basket;
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
        $this->basketElements[$basketElement->reference()->referenceValue()]['quantity'] = $basketElement->quantity()->value();
        $this->basketElements[$basketElement->reference()->referenceValue()]['price'] = $basketElement->reference()->price();
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
        unset($this->basketElements[$reference->referenceValue()]);
    }

    /**
     * @param BasketElement $basketElement
     * @param BasketAction $action
     * @return void
     * @throws NotFountElementInBasketException
     */
    public function updateBasket( BasketElement $basketElement, BasketAction  $action ): void
    {
        $existingElement = $this->findOneElement($basketElement->reference());
        if(!$existingElement){
            $this->throwNotFountElementInBasketExceptionIfActionIsNotAddToBasket($action);
            $this->addElementToBasket($basketElement);
            return;
        }

        if($action === BasketAction::DECREASE_QUANTITY){
            $existingQuantity = $existingElement->quantity()->value();
            if($existingQuantity < $basketElement->quantity()->value()){
                throw new NotAllowedQuantityToRemove(
                    'Vous ne pouvez pasm retirer plus de <'. $existingQuantity.'> fruits dans votre panier!');
            }
            $existingElement->decreaseQuantity($basketElement->quantity()->value());
            $existingQuantity > 0 ? $this->addElementToBasket($existingElement) : $this->removeElementFromBasket($basketElement->reference());
            return;
        }

        if($action === BasketAction::ADD_TO_BASKET )
        {
            $existingElement->increaseQuantity( $basketElement->quantity()->value() );
            $this->addElementToBasket($existingElement);

            return;
        }

        $this->removeElementFromBasket($basketElement->reference());
        count($this->basketElements) !== 0 ? : $this->changeStatus(BasketStatus::IS_DESTROYED);
    }

    public function status(): BasketStatus
    {
        return $this->status;
    }

    public function changeStatus(BasketStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @param FruitReference $elementReference
     * @return BasketElement|null
     */
    public function findOneElement(FruitReference $elementReference): ?BasketElement
    {
        $keyExist = array_key_exists($elementReference->referenceValue(), $this->basketElements);
        if($keyExist){
            $foundElement = $this->basketElements[$elementReference->referenceValue()];
            $asObject = new BasketElement($elementReference);
            $asObject->quantity = new Quantity($foundElement['quantity']);
            return $asObject ;
        }
        return null ;
    }

    /**
     * @return void
     */
    public function makeBasketEmpty():void
    {
        $this->basketElements = [];
    }

    public function reverseElementsOrder():void
    {
        $this->basketElements = array_reverse($this->basketElements);
    }

    /**
     * @param BasketAction $action
     * @return void
     * @throws NotFountElementInBasketException
     */
    private function throwNotFountElementInBasketExceptionIfActionIsNotAddToBasket(BasketAction $action): void
    {
        if ($action !== BasketAction::ADD_TO_BASKET) {
            throw new NotFountElementInBasketException(
                'L\'element que vous souhaitez manipuler la qunatité n\'existe pas dans votre panier'
            );
        }
    }

}