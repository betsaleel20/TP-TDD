<?php

namespace Tests\Units\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\InvalidArgumentsException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Services\ChangeFruitsStatusOfValidatedBasketToSoldService;
use App\Application\Services\GetSoldFruitsService;
use App\Application\Services\VerifyIfFruitReferenceExistService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\UseCases\ValidateBasketHandler;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\NeededQuantity;
use App\Application\ValueObjects\BasketElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use PHPUnit\Framework\TestCase;

class ValidateBasketTest extends TestCase
{
    private BasketRepository $basketRepository;
    private FruitRepository $fruitRepository;

    public function setUp():void
    {
        parent::setUp();
        $this->basketRepository = new InMemoryBasketRepository();
        $this->fruitRepository = new InMemoryFruitRepository();
    }


    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_validate_basket()
    {
        //given
        $element = new BasketElement(
            reference:          new FruitReference('Ref01'),
            orderedQuantity:    new NeededQuantity(3)
        );
        $basket = Basket::create($element);
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $handler = $this->buildBasketHandler();
        $response = $handler->handle($command);

        //then
        $this->assertTrue($response->isValidated);
        $this->assertNotNull($response->orderId);
        $this->assertNotNull($basket->paymentMethod()->value);
        $this->assertNotNull($basket->currency()->value);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_not_found_basket_exception()
    {
        $element = new BasketElement(new FruitReference('Ref01'), new NeededQuantity(3));
        $order = Basket::create($element);
        $this->basketRepository->save($order);
        $command = new ValidateBasketCommand(
            id:20,
            paymentMethod: 1,
            currency:2
        );

        $handler = $this->buildBasketHandler();

        $this->expectException(NotFoundBasketException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_arguments_exception_when_payment_method_is_invalid()
    {
        $element = new BasketElement(new FruitReference('Ref01'), new NeededQuantity(3));
        $order = Basket::create($element);
        $this->basketRepository->save($order);
        $command = new ValidateBasketCommand(
            id:$order->id()->value(),
            paymentMethod: 101,
            currency:1
        );

        $handler = $this->buildBasketHandler();

        $this->expectException(InvalidArgumentsException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_arguments_exception_when_currency_is_invalid()
    {
        $element1 = new BasketElement(new FruitReference('Ref01'), new NeededQuantity(3));
        $order = Basket::create($element1);
        $this->basketRepository->save($order);
        $command = new ValidateBasketCommand(
            id:$order->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:10
        );

        $handler = $this->buildBasketHandler();
        $this->expectException(InvalidArgumentsException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_not_found_reference_exception()
    {
        $element1 = new BasketElement(new FruitReference('undefinedRef'), new NeededQuantity(3));
        $order = Basket::create($element1);
        $this->basketRepository->save($order);
        $command = new ValidateBasketCommand(
            id:$order->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:Currency::DOLLAR->value
        );

        $handler = $this->buildBasketHandler();
        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_unavailable_fruit_quantity_in_stock_exception()
    {
        $basketElement1 = new BasketElement(
            new FruitReference('Ref01'),
            new NeededQuantity(30)
        );
        $basket = Basket::create($basketElement1);
        $basketElement2 = new BasketElement(
            reference: new FruitReference('Ref02'),
            orderedQuantity:  new NeededQuantity(3)
        );
        $basket->addElementToBasket($basketElement2);
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:$basket->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:Currency::DOLLAR->value
        );

        $handler = $this->buildBasketHandler();

        $this->expectException(UnavailableFruitQuantityException::class);
        $handler->handle($command);
    }

    /**
     * @return ValidateBasketHandler
     */
    public function buildBasketHandler(): ValidateBasketHandler
    {
         return new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository
        );
    }

}