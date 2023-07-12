<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\InvalidFruitReferenceException;
use App\Application\UseCases\ValidateOrderHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class ValidateOrderTest extends TestCase
{
    private OrderRepository $orderRepository;
    public function setUp():void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
    }


    public function test_can_validate_order()
    {
        //given
        $element1 = new OrderElement(new FruitReference('Ref01'), new OrderedQuantity(3));
        $order = Order::create($element1);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            $order->id()->value(),
            PaymentMethod::VISA->value,
            Currency::DOLLAR->value
        );

        //When
        $handler = new ValidateOrderHandler($this->orderRepository);
        $response = $handler->handle($command);

        //then
        $this->assertTrue($response->isValidated);
        $this->assertIsString($response->orderId);
    }

    public function test_can_throw_invalid_order_argument_exception()
    {
        $element1 = new OrderElement(new FruitReference('Ref01'), new OrderedQuantity(3));
        $order = Order::create($element1);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand( $order->id()->value(),00,100);

        $handler = new ValidateOrderHandler($this->orderRepository);
        $this->expectException(\InvalidArgumentException::class);
        $handler->handle($command);
    }

    public function test_can_throw_invalid_fruit_reference_exception()
    {
        $element1 = new OrderElement(new FruitReference('Ref0010'), new OrderedQuantity(3));
        $order = Order::create($element1);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            $order->id()->value(),
            PaymentMethod::VISA->value,
            Currency::DOLLAR->value
        );

        $handler = new ValidateOrderHandler($this->orderRepository);
        $this->expectException(InvalidFruitReferenceException::class);
        $handler->handle($command);
    }

}