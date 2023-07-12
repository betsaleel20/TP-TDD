<?php

namespace Tests\Units\Order;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Services\ChangeFruitsStatusOfValidatedOrderToSoldService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\UseCases\ValidateOrderHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class ValidateOrderTest extends TestCase
{
    private OrderRepository $orderRepository;
    private FruitRepository $fruitRepository;

    public function setUp():void
    {
        parent::setUp();
        $this->orderRepository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }


    /**
     * @return void
     */
    public function test_can_validate_order()
    {
        //given
        $element1 = new OrderElement(
            reference:new FruitReference('Ref01'),
            orderedQuantity:new OrderedQuantity(3)
        );
        $order = Order::create($element1);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            $order->id()->value(),
            PaymentMethod::VISA->value,
            Currency::DOLLAR->value
        );

        //When
        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository),
            new ChangeFruitsStatusOfValidatedOrderToSoldService($this->fruitRepository)
        );
        $response = $handler->handle($command);

        //then
        $this->assertTrue($response->isValidated);
        $this->assertIsString($response->orderId);
        $this->assertNotNull($order->paymentMethod()->value);
        $this->assertEquals($response->paymentMethod, $order->paymentMethod()->value);
        $this->assertNotNull($order->currency()->value);
        $this->assertEquals($response->currency, $order->currency()->value);
        $this->assertEquals(OrderStatus::IS_VALIDATED->value, $order->status()->value);

    }

    public function test_can_change_ordered_fruits_status()
    {
        $element1 = new OrderElement(
            reference:new FruitReference('Ref01'),
            orderedQuantity:new OrderedQuantity(3)
        );
        $order = Order::create($element1);

        $element2 = new OrderElement(
            reference:new FruitReference('Ref02'),
            orderedQuantity:new OrderedQuantity(3)
        );

        $element3 = new OrderElement(
            reference:new FruitReference('Ref03'),
            orderedQuantity:new OrderedQuantity(1)
        );
        $order->addElementToOrder($element2);
        $order->addElementToOrder($element3);

        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            $order->id()->value(),
            PaymentMethod::MASTERCARD->value,
            Currency::XAF->value
        );


        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository),
            new ChangeFruitsStatusOfValidatedOrderToSoldService($this->fruitRepository)
        );
        $response = $handler->handle($command);


        $this->assertTrue($response->isValidated);
        $this->assertIsString($response->orderId);
    }

    /**
     * @return void
     */
    public function test_can_throw_invalid_command_arguments_exception()
    {
        $element1 = new OrderElement(new FruitReference('Ref01'), new OrderedQuantity(3));
        $order = Order::create($element1);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            id:$order->id()->value(),
            paymentMethod: 1,
            currency:10
        );

        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository),
            new ChangeFruitsStatusOfValidatedOrderToSoldService($this->fruitRepository)
        );
        $this->expectException(\InvalidArgumentException::class);
        $handler->handle($command);
    }

    public function test_can_throw_unavailable_fruit_quantity_in_stock_exception()
    {
        $orderedElement1 = new OrderElement(new FruitReference('Ref01'), new OrderedQuantity(2));
        $order = Order::create($orderedElement1);
        $orderedElement2 = new OrderElement(new FruitReference('Ref02'), new OrderedQuantity(30));
        $order->addElementToOrder($orderedElement2);
        $this->orderRepository->save($order);
        $command = new ValidateOrderCommand(
            id:$order->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:Currency::DOLLAR->value
        );

        $handler = new ValidateOrderHandler(
            $this->orderRepository,
            new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository),
            new ChangeFruitsStatusOfValidatedOrderToSoldService($this->fruitRepository)
        );

        $this->expectException(UnavailableFruitQuantityException::class);
        $handler->handle($command);
    }

}