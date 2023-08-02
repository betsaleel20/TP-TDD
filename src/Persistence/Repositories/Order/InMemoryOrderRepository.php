<?php

namespace App\Persistence\Repositories\Order;

use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\ValueObjects\Id;

class InMemoryOrderRepository implements OrderRepository
{
    private array $orders = [];

    public function save(Order $order):void
    {
        $this->orders[$order->id()->value()] = $order;
    }

    public function getOrder(Id $orderId): ?Order
    {
        return $this->orders[$orderId->value()];
    }
}