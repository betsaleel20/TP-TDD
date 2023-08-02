<?php

namespace App\Application\Entities\Order;

use App\Application\Entities\Fruit\Fruit;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\ValueObjects\Id;

class Order
{
    private  Id $id;
    private ?PaymentMethod $paymentMethod;
    private ?Currency $currency;
    /**
     * @var Fruit[]
     */
    private array $soldFruits;

    private ?OrderStatus $status;
    private float $discount;
    private float $billAmount;

    private function __construct(Id $id,
                                 array $fruitsToSale,
                                 PaymentMethod $paymentMethod,
                                 Currency $currency
    )
    {
        $this->id = $id;
        $this->paymentMethod = $paymentMethod;
        $this->currency = $currency;
        $this->soldFruits = $fruitsToSale;
        $this->status = null;
        $this->discount = 0.0;
        $this->billAmount = 0.0;
    }

    /**
     * @param Fruit[] $fruitsToSale
     * @param PaymentMethod $paymentMethod
     * @param Currency $currency
     * @return self
     */
    public static function create(
        array         $fruitsToSale,
        PaymentMethod $paymentMethod,
        Currency      $currency
    ):self
    {
        $order = new self(new Id(time()), $fruitsToSale, $paymentMethod, $currency);
        $order->status = OrderStatus::IS_CREATED;
        $order->billAmount = $order->calculateTotalCost();

        return $order;
    }

    /**
     * @return Id
     */
    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function totalCost():float
    {
        return $this->billAmount;
    }

    /**
     * @return float
     */
    public function discount():float
    {
        return $this->discount;
    }

    /**
     * @return Fruit[]
     */
    public function soldFruits():array
    {
        return $this->soldFruits;
    }

    /**
     * @return OrderStatus
     */
    public function status(): OrderStatus
    {
        return $this->status;
    }

    /**
     * @return float
     */
    public function calculateTotalCost():float
    {
        $totalCost = 0.0;
        $fruits = $this->soldFruits;
        foreach ($fruits as $fruit) {
            $totalCost = $totalCost + $fruit->reference()->price();
        }
        return $totalCost;
    }

    public function discountOnFirstElement( int $numberOfFruits, float $price ):void
    {
        $discount = round($numberOfFruits * $price *10 / 100, 2);
        $this->discount += $discount;
        $this->billAmount = $this->calculateTotalCost() - $this->discount;

    }

    /**
     * @param int $percentage
     * @return void
     */
    public function applyDiscount(int $percentage):void
    {
        $totalCost = $this->calculateTotalCost();
        $discount = round($totalCost * $percentage/100,2);
        $this->discount += $discount;
        $this->billAmount = $totalCost - $this->discount;
    }

}