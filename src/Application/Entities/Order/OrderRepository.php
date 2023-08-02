<?php

namespace App\Application\Entities\Order;

use App\Application\ValueObjects\Id;

interface OrderRepository
{
    public function save(Order $order);
    public function getOrder(Id $orderId):?Order;
}