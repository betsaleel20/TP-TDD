<?php

namespace Tests\Units\Order;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\OrderAction;
use App\Application\Enums\OrderStatus;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Exceptions\NotFountOrderElementException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\UseCases\SaveOrderHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Order\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

class SaveOrderTest extends TestCase
{

    private OrderRepository $repository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryOrderRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_create_an_order()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            2
        );

        //When
        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals(OrderStatus::PENDING->value, $response->orderStatus);
    }

    /**
     * @throws NotFoundFruitReferenceException
     */
    public function test_can_add_element_to_order()
    {
        $existingOrder = $this->buildOrderSUT();

        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            2
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
        $this->assertEquals(OrderStatus::PENDING->value, $response->orderStatus);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_remove_order_element()
    {
        $existingOrder = $this->buildOrderSUT();

        $command = new SaveOrderCommand(
            'Ref03',
            1
        );
        $command->orderId = $existingOrder->id()->value();
        $command->action = OrderAction::REMOVE_FROM_ORDER->value;

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertNotNull($response->orderId);
        $this->assertEquals($response->orderStatus, OrderStatus::ONE_ELEMENT_REMOVED->value);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_destroy_order_while_removing_last_element_from_existing_order()
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(2)
        );

        $existingOrder = Order::create(
            orderElement: $orderElement
        );
        $this->repository->save($existingOrder);

        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            2
        );
        $command->orderId = $existingOrder->id()->value();
        $command->action = OrderAction::REMOVE_FROM_ORDER ->value;

        $handler = $this->createSaveOrderHandler();
        $response = $handler->handle($command);

        $this->assertTrue($response->isSaved);
        $this->assertEquals(OrderStatus::IS_DESTROYED->value, $response->orderStatus);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_unavailable_product_quantity_in_stock()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            10
        );
        $command->orderId = $existingOrder->id()->value();
        $handler = $this->createSaveOrderHandler();

        $this->expectException(UnavailableFruitQuantityException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_order_not_found_exception()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            2
        );
        $command->orderId = 'azeaze';

        $handler = $this->createSaveOrderHandler();

        $this->expectException(NotFoundOrderException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundOrderException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_fruit_ref()
    {
        $command = new SaveOrderCommand('', 5);

        $handler = $this->createSaveOrderHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundFruitReferenceException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_ordered_quantity()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = new SaveOrderCommand(
            $existingOrder->orderElements()[0]->reference()->value(),
            -5
        );

        $handler = $this->createSaveOrderHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @throws NotFoundOrderException
     */
    public function test_can_throw_fruit_reference_not_found_exception()
    {
        $command = new SaveOrderCommand(
            fruitRef: 'Ref10',
            orderedQuantity: 10,
        );

        $handler = $this->createSaveOrderHandler();

        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    private function buildOrderSUT(): Order
    {
        $orderElement = new OrderElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new OrderedQuantity(2)
        );

        $existingOrder = Order::create(
            orderElement: $orderElement,
            id: new Id('001')
        );

        $orderElement2 = new OrderElement(
            reference: new FruitReference('Ref03'),
            orderedQuantity: new OrderedQuantity(1)
        );
        $existingOrder->addElementToOrder($orderElement2);
        $this->repository->save($existingOrder);

        return $existingOrder;
    }

    /**
     * @return SaveOrderHandler
     */
    public function createSaveOrderHandler(): SaveOrderHandler
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
        $verifyIfThereIsEnoughFruitInStockService = new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository);

        return new SaveOrderHandler(
            $this->repository,
            $getFruitByReferenceService,
            $verifyIfThereIsEnoughFruitInStockService
        );
    }
}