<?php

namespace App\Application\Entities\Basket;

use App\Application\Enums\Currency;
use App\Application\Enums\OrderAction;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\NotFountOrderElementException;
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

    private InMemoryFruitRepository $fruitRepository;

    /**
     * @param Id $id
     */
    private function __construct(
        readonly private Id $id
    )
    {
        $this->fruitRepository = new InMemoryFruitRepository();
        $this->basketElements = [];
    }

    public static function create(
        BasketElement $basketElement,
        ?Id           $id = null,
    ): self
    {
        $self = new self($id ?? new Id(time()));
        $self->addElementToBasket($basketElement);
        $self->changeStatus(BasketStatus::PENDING);

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
     * @param BasketElement $orderElement
     * @return void
     */
    public function addElementToBasket(BasketElement $orderElement): void
    {
        $this->basketElements[] = $orderElement;
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
            fn(BasketElement $e) => $e->reference()->value() !== $reference->value()
        ));

        if (count($this->basketElements) === 0) {
            $this->changeStatus(BasketStatus::IS_DESTROYED);
        }
    }

    /**
     * @throws NotFountOrderElementException
     */
    public function updateBasketElement(
        BasketElement $orderElement,
        OrderAction   $action
    ): void
    {
        $state = $this->checkElementExistence($orderElement->reference());
        if ($action === OrderAction::ADD_TO_ORDER) {
            if($state){
                $this->updateAddedElementQuantity($orderElement);
                return;
            }
            $this->addElementToBasket($orderElement);
            return;
        }
        if(!$state){
            throw new NotFountOrderElementException('L\'element que vous souhaitez supprimer n\'existe pas');
        }
        $this->removeElementFromBasket($orderElement->reference());
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
            fn(BasketElement $oe)=>$oe->reference()->value() === $reference->value()
        ));
        return count($foundElement) > 0;
    }

    /**
     * @param BasketElement $elementAddedToBasket
     * @return void
     */
    public function updateAddedElementQuantity(BasketElement $elementAddedToBasket):void
    {
        $elementAddedToBasket->changeQuantity($elementAddedToBasket->quantity());
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


}