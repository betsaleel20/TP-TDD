<?php

namespace Tests\Units\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\OrderAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundElementException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFountOrderElementException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\UseCases\SaveBasketHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\NeededQuantity;
use App\Application\ValueObjects\BasketElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use PHPUnit\Framework\TestCase;

class SaveBasketTest extends TestCase
{
    private BasketRepository $repository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryBasketRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_create_an_basket()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::ADD_TO_ORDER->value,
            2
        );
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($response->orderStatus, BasketStatus::PENDING->value );
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_add_element_to_basket()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::ADD_TO_ORDER->value,
            1
        );
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals($command->orderId, $response->orderId);
        $this->assertEquals(BasketStatus::PENDING->value, $response->orderStatus);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_invalid_command_exception_with_invalid_ordered_quantity()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::ADD_TO_ORDER->value,
            -5
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveBasketHandler();

        $this->expectException(InvalidCommandException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_not_found_fruit_reference_exception_when_adding_element_to_basket()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            'fakeFruitRef',
            OrderAction::ADD_TO_ORDER->value,
            1
        );
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();

        //Then
        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_unavailable_fruit_quantity_in_stock_exception()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::ADD_TO_ORDER->value,
            10
        );
        $command->orderId = $existingOrder->id()->value();
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();

        //Then
        $this->expectException(UnavailableFruitQuantityException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_not_found_basket_exception_when_adding_element_to_basket()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::ADD_TO_ORDER->value,
            1
        );
        $command->orderId = 1;

        //When
        $handler = $this->createSaveBasketHandler();

        //Then
        $this->expectException(NotFoundBasketException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_remove_element_from_basket()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::REMOVE_FROM_ORDER->value
        );
        $command->orderId = $existingOrder->id()->value();

        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_not_found_element_exception_when_removing_element_from_basket()
    {
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            'refToRemove',
            OrderAction::REMOVE_FROM_ORDER->value
        );
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();

        //Then
        $this->expectException(NotFoundElementException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountOrderElementException
     */
    public function test_can_destroy_basket_while_removing_last_element_from_existing_basket()
    {
        //Given
        $orderElement = new BasketElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new NeededQuantity(2)
        );
        $existingOrder = Basket::create(
            basketElement: $orderElement
        );
        $this->repository->save($existingOrder);
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::REMOVE_FROM_ORDER->value,
        );
        $command->orderId = $existingOrder->id()->value();

        //When
        $handler = $this->createSaveBasketHandler();
        $response = $handler->handle($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals(BasketStatus::IS_DESTROYED->value, $response->orderStatus);
        $this->assertNotNull($response->orderId);
        $this->assertEquals($command->orderId, $response->orderId);
    }

    /**
     * @throws NotFountOrderElementException
     */
    public function test_can_throw_not_found_basket_exception_when_removing_element_from_basket()
    {
        //Given
        $existingOrder = $this->buildOrderSUT();
        $command = SaveBasketCommand::create(
            $existingOrder->basketElements()[0]->reference()->value(),
            OrderAction::REMOVE_FROM_ORDER->value
        );
        $command->orderId = 1;

        //When
        $handler = $this->createSaveBasketHandler();

        //Then
        $this->expectException(NotFoundBasketException::class);
        $handler->handle($command);
    }

    /**
     * @return Basket
     */
    private function buildOrderSUT(): Basket
    {
        $orderElement = new BasketElement(
            reference: new FruitReference('Ref01'),
            orderedQuantity: new NeededQuantity(2)
        );

        $existingOrder = Basket::create(
            basketElement: $orderElement
        );

        $orderElement2 = new BasketElement(
            reference: new FruitReference('Ref03'),
            orderedQuantity: new NeededQuantity(1)
        );
        $existingOrder->addElementToBasket($orderElement2);
        $this->repository->save($existingOrder);

        return $existingOrder;
    }

    /**
     * @return SaveBasketHandler
     */
    public function createSaveBasketHandler(): SaveBasketHandler
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
        $verifyIfThereIsEnoughFruitInStockService = $this->getIsEnoughFruitInStockService();

        return $this->getOrderHandler($getFruitByReferenceService, $verifyIfThereIsEnoughFruitInStockService);
    }

    /**
     * @param GetFruitByReferenceService $getFruitByReferenceService
     * @param VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStockService
     * @return SaveBasketHandler
     */
    public function getOrderHandler(GetFruitByReferenceService $getFruitByReferenceService, VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStockService): SaveBasketHandler
    {
        return new SaveBasketHandler(
            $this->repository,
            $getFruitByReferenceService,
            $verifyIfThereIsEnoughFruitInStockService
        );
    }

    /**
     * @return VerifyIfThereIsEnoughFruitInStockService
     */
    public function getIsEnoughFruitInStockService(): VerifyIfThereIsEnoughFruitInStockService
    {
        return new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository);
    }
}