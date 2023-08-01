<?php

namespace App\Persistence\Repositories\Order;

use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;

class InMemoryOrderRepository implements OrderRepository
{
    private array $orders = [];

    public function save(Order $order):void
    {
        $this->orders[$order->id()->value()] = $order;
    }
}