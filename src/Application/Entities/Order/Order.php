<?php

namespace App\Application\Entities\Order;

use App\Application\Enums\Currency;
use App\Application\Enums\OrderAction;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFountOrderElementException;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class Order
{

    /**
     * @var OrderElement[]
     */
    private array $orderElements;
    private OrderStatus $status;

    private InMemoryFruitRepository $fruitRepository;

    /**
     * @param Id $id
     */
    private function __construct(
        readonly private Id $id
    )
    {
        $this->orderElements = [];
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    public static function create(
        OrderElement  $orderElement,
        ?Id          $id = null,
    ): self
    {
        $self = new self($id ?? new Id(time()));
        $self->addElementToOrder($orderElement);
        $self->changeStatus(OrderStatus::PENDING);

        return $self;
    }

    /**
     * @return Id|null
     */
    public function id(): ?Id
    {
        return $this->id;
    }

    /**
     * @param OrderElement $orderElement
     * @return void
     */
    public function addElementToOrder(OrderElement $orderElement): void
    {
        $this->orderElements[] = $orderElement;
    }


    /**
     * @return OrderElement[]
     */
    public function orderElements(): array
    {
        return $this->orderElements;
    }

    private function removeElementFromOrder(OrderElement $orderElement): void
    {
        $this->orderElements = array_values(array_filter(
            $this->orderElements,
            fn(OrderElement $e) => $e->reference()->value() !== $orderElement->reference()->value()
        ));
        $this->changeStatus(OrderStatus::ONE_ELEMENT_REMOVED);

        if (count($this->orderElements) === 0) {
            $this->changeStatus(OrderStatus::IS_DESTROYED);
        }
    }

    /**
     * @throws NotFountOrderElementException
     */
    public function updateOrderElement(
        OrderElement $orderElement,
        OrderAction  $action
    ): void
    {
        $orderElementToChange = $this->findOrderedElementByReference($orderElement->reference());
        if ($action === OrderAction::ADD_TO_ORDER) {
            if($orderElementToChange){
                $this->updateOrderedElementQuantity($orderElementToChange);
                return;
            }
            $this->addElementToOrder($orderElement);
            return;
        }
        if(!$orderElementToChange){
            throw new NotFountOrderElementException('L\'element que vous souhaitez supprimer n\'existe pas');
        }
        $this->removeElementFromOrder($orderElement);
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    private function changeStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @param FruitReference $reference
     * @return OrderElement|null
     */
    public function findOrderedElementByReference(FruitReference $reference): ?OrderElement
    {
        $foundElement = array_values(array_filter(
            $this->orderElements,
            fn(OrderElement $oe)=>$oe->reference()->value() === $reference->value()
        ));
        return count($foundElement) > 0 ? $foundElement[0] : null;
    }

    public function updateOrderedElementQuantity(OrderElement $orderElement):void
    {
        $orderElement->changeQuantity($orderElement->quantity());
    }


}