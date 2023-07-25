<?php

namespace App\Application\Entities\Order;

use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\ValueObjects\Id;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class Order
{
    private ?PaymentMethod $paymentMethod;
    private ?Currency $currency;
    /**
     * @var Fruit[]
     */
    private array $soldFruits;
    private ?OrderStatus $status;

    private float $discount;
    private InMemoryFruitRepository $fruitRepository;

    private function __construct(private readonly Id $id)

    {
        $this->paymentMethod = null;
        $this->currency = null;
        $this->soldFruits = [];
        $this->status = null;
        $this->discount = 0.0;
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @param array $fruitsToSold
     * @param PaymentMethod $paymentMethod
     * @param Currency $currency
     * @return self
     */
    public static function create(
        array $fruitsToSold,
        PaymentMethod $paymentMethod,
        Currency $currency
    ):self
    {
        $order = new self(new Id(time()));
        $order->status = OrderStatus::IS_CREATED;
        $order->soldFruits = $fruitsToSold;
        $order->paymentMethod = $paymentMethod;
        $order->currency = $currency;

        return $order;
    }

    /**
     * @return Id
     */
    public function id(): Id
    {
        return $this->id;
    }

}