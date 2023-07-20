<?php

namespace App\Application\Entities\Order;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\ValueObjects\Id;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class Order
{
    private Id $id;
    private ?PaymentMethod $paymentMethod;
    private ?Currency $currency;
    /**
     * @var Fruit[]
     */
    private array $soldFruits;
    private ?OrderStatus $status;

    private float $discount;
    private InMemoryFruitRepository $fruitRepository;

    private function __construct(private Id $basketId)

    {
        $this->id = new Id(time());
        $this->paymentMethod = null;
        $this->currency = null;
        $this->soldFruits = [];
        $this->status = null;
        $this->discount = 0;
        $this->fruitRepository = new InMemoryFruitRepository();
    }
    public static function create(
        Id $basketId,
        PaymentMethod $paymentMethod,
        Currency $currency,
        float $discount
    ):self
    {
        $order = new self($basketId);
        $order->paymentMethod = $paymentMethod;
        $order->currency = $currency;
        $order->discount = $discount;
        return $order;
    }

    public function id():Id
    {
        return $this->id;
    }

    /**
     * @param Fruit[] $soldFruits
     * @return void
     */
    public function addFruitsToOrder(array $soldFruits):void
    {
        foreach ($soldFruits as $soldFruit) {
            $this->addOneFruitToOrder($soldFruit);
        }
    }

    private function addOneFruitToOrder(Fruit $fruit):void
    {
        $this->soldFruits[] = $fruit;
    }

}