<?php

namespace App\Application\Entities\Order;

interface OrderRepository
{
    public function save(Order $order);
}