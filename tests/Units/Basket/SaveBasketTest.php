<?php

namespace Tests\Units\Basket;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\SaveBasketResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\UseCases\Basket\SaveBasketHandler;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\NeededQuantity;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use PHPUnit\Framework\TestCase;

class SaveBasketTest extends TestCase
{
    private BasketRepository $basketRepository;
    private FruitRepository $fruitRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->basketRepository = new InMemoryBasketRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_create_a_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 2
        );

        //When
        $response = $this->saveBasket($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertNotNull($response->basketId);
        $this->assertEquals($response->basketStatus, BasketStatus::IS_SAVED->value );
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_add_element_to_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $newReference = 'Ref03';
        $command = SaveBasketCommand::create(
            fruitRef: $newReference,
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 3
        );
        $command->basketId = $existingBasket->id()->value();

        //When
        $response = $this->saveBasket($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_basket_exception_when_adding_element_to_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 1
        );
        $incorrectBasketId = 'badId';
        $command->basketId = $incorrectBasketId;

        //When & Then
        $this->expectException(NotFoundBasketException::class);
        $this->saveBasket($command);

    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_fruit_reference_exception_when_adding_element_to_basket()
    {
        $existingBasket = $this->buildBasketSUT();
        $fakeReference = 'fakeRef';
        $command = SaveBasketCommand::create(
            fruitRef: $fakeReference,
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 1
        );
        $command->basketId = $existingBasket->id()->value();

        //When & Then
        $this->expectException(NotFoundFruitReferenceException::class);
        $this->saveBasket($command);

    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_unavailable_fruit_quantity_in_stock_exception_when_adding_new_element_to_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $newReference = 'Ref03';
        $command = SaveBasketCommand::create(
            fruitRef: $newReference,
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 7
        );
        $command->basketId = $existingBasket->id()->value();

        //When & Then
        $this->expectException(UnavailableFruitQuantityException::class);
        $this->saveBasket($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */

    public function test_can_throw_invalid_command_exception_when_adding_element_to_basket_with_invalid_needed_quantity()
    {
        $existingBasket = $this->buildBasketSUT();
        $invalidQuantity = -3;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: $invalidQuantity
        );
        $command->basketId = $existingBasket->id()->value();

        $this->expectException(InvalidCommandException::class);
        $this->saveBasket($command);

    }


    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_update_basket_by_increasing_needed_quantity_for_a_basket_element(){
        //Given
        $existingBasket = $this->buildBasketSUT();
        $initialQuantity = $existingBasket->basketElements()[1]->quantity()->value();
        $quantityToAdd = 2;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[1]->reference()->referenceValue(),
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: $quantityToAdd
        );
        $command->basketId = $existingBasket->id()->value();

        //When
        $response = $this->saveBasket($command);
        //Then
        $finalQuantity = $existingBasket->basketElements()[1]->quantity()->value();
        $this->assertTrue($response->isSaved);
        $this->assertGreaterThan($initialQuantity, $finalQuantity);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_unavailable_stock_quantity_exception_when_increasing_needed_quantity(){
        //Given
        $existingBasket = $this->buildBasketSUT();
        $existingReference = 'Ref02';
        $command = SaveBasketCommand::create(
            fruitRef: $existingReference,
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: 5
        );
        $command->basketId = $existingBasket->id()->value();

        //When && Then
        $this->expectException(UnavailableFruitQuantityException::class);
        $this->saveBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_invalid_command_exception_when_updating_basket_element(){
        //Given
        $existingBasket = $this->buildBasketSUT();
        $invalidQuantity = -10;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[1]->reference()->referenceValue(),
            action: BasketAction::ADD_TO_BASKET->value,
            neededQuantity: $invalidQuantity
        );
        $command->basketId = $existingBasket->id()->value();

        //When && Then
        $this->expectException(InvalidCommandException::class);
        $this->saveBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_update_basket_by_decreasing_needed_quantity_for_a_basket_element(){
        //Given
        $existingBasket = $this->buildBasketSUT();
        $initialQuantity = $existingBasket->basketElements()[1]->quantity()->value();
        $quantityToRemove = 2;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[1]->reference()->referenceValue(),
            action: BasketAction::REMOVE_FROM_BASKET->value,
            neededQuantity: $quantityToRemove
        );
        $command->basketId = $existingBasket->id()->value();

        //When
        $response = $this->saveBasket($command);
        //Then
        $finalQuantity = $existingBasket->basketElements()[1]->quantity()->value();
        $this->assertTrue($response->isSaved);
        $this->assertLessThan($initialQuantity, $finalQuantity);
        $this->assertEquals($command->basketId, $response->basketId);
        $this->assertEquals(BasketStatus::IS_SAVED->value, $response->basketStatus);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_unavailable_stock_quantity_exception_when_decreasing_needed_quantity(){
        //Given
        $existingBasket = $this->buildBasketSUT();
        $quantityToRemove = 4;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[1]->reference()->referenceValue(),
            action: BasketAction::REMOVE_FROM_BASKET->value,
            neededQuantity: $quantityToRemove
        );
        $command->basketId = $existingBasket->id()->value();

        //When && Then
        $this->expectException(InvalidCommandException::class);
        $this->saveBasket($command);
    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_remove_element_from_basket()
    {
        $existingBasket = $this->buildBasketSUT();
        $expectedRemainingElementAfterRemove = count($existingBasket->basketElements()) - 1;
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action: BasketAction::REMOVE_FROM_BASKET->value,
        );
        $command->basketId = $existingBasket->id()->value();

        // When
        $response = $this->saveBasket($command);
        $remainingElements = count($existingBasket->basketElements());


        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals($expectedRemainingElementAfterRemove, $remainingElements);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_reference_exception_when_removing_element_from_basket()
    {
        $existingBasket = $this->buildBasketSUT();
        $fakeFruitReference = 'fakeRef012';
        $command = SaveBasketCommand::create(
            fruitRef: $fakeFruitReference,
            action: BasketAction::REMOVE_FROM_BASKET->value,
            neededQuantity: 2
        );
        $command->basketId = $existingBasket->id()->value();

        //When & Then
        $this->expectException(NotFoundFruitReferenceException::class);
        $this->saveBasket($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_basket_exception_when_removing_element_from_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUT();
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action: BasketAction::REMOVE_FROM_BASKET->value,
        );
        $incorrectId = 1;
        $command->basketId = $incorrectId;

        //When & Then
        $this->expectException(NotFoundBasketException::class);
        $this->saveBasket($command);

    }

    /**
     * @return void
     * @throws NotFoundFruitReferenceException
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_destroy_basket_while_removing_last_element_from_existing_basket()
    {
        //Given
        $existingBasket = $this->buildBasketSUTWithOneElement();
        $command = SaveBasketCommand::create(
            fruitRef: $existingBasket->basketElements()[0]->reference()->referenceValue(),
            action :BasketAction::REMOVE_FROM_BASKET->value
        );
        $command->basketId = $existingBasket->id()->value();

        //When
        $response = $this->saveBasket($command);

        //Then
        $this->assertTrue($response->isSaved);
        $this->assertEquals(BasketStatus::IS_DESTROYED->value, $response->basketStatus);
        $this->assertEquals($command->basketId, $response->basketId);
    }

    /**
     * @return Basket
     * @throws NotFountElementInBasketException
     */
    private function buildBasketSUT(): Basket
    {
        $basketElement = new BasketElement(
            reference: new FruitReference(reference: 'Ref01', price: 1000)
        );
        $basketElement->neededQuantity = new NeededQuantity(2);

        $existingBasket = Basket::create(
            newBasketElement: $basketElement,
            action: BasketAction::ADD_TO_BASKET
        );

        $basketElement2 = new BasketElement(
            reference: new FruitReference(reference: 'Ref02', price: 2000)
        );
        $basketElement2->neededQuantity = new NeededQuantity(3);
        $existingBasket->addElementToBasket($basketElement2);
        $this->basketRepository->save($existingBasket);

        return $existingBasket;
    }

    /**
     * @param SaveBasketCommand $command
     * @return SaveBasketResponse
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    private function saveBasket(SaveBasketCommand $command): SaveBasketResponse
    {
        $getFruitByReferenceService = new GetFruitByReferenceService($this->fruitRepository);
        $verifyIfThereIsEnoughFruitInStockService = new VerifyIfThereIsEnoughFruitInStockService($this->fruitRepository);

        $handler = new SaveBasketHandler(
            $this->basketRepository,
            $getFruitByReferenceService,
            $verifyIfThereIsEnoughFruitInStockService
        );

        return $handler->handle($command);
    }

    /**
     * @return Basket
     * @throws NotFountElementInBasketException
     */
    public function buildBasketSUTWithOneElement(): Basket
    {
        $goodFruitReference = new FruitReference('Ref03');
        $basketElement = new BasketElement(
            reference: $goodFruitReference,
        );
        $basketElement->neededQuantity = new NeededQuantity(2);
        $existingBasket = Basket::create(
            newBasketElement: $basketElement,
            action: BasketAction::REMOVE_FROM_BASKET
        );
        $this->basketRepository->save($existingBasket);
        return $existingBasket;
    }
}