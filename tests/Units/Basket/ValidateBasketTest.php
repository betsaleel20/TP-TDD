<?php

namespace Tests\Units\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\InvalidArgumentsException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\UseCases\Basket\ValidateBasketHandler;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\NeededQuantity;
use App\Persistence\Repositories\Basket\InMemoryBasketRepository;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;
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
     * @throws NotFountElementInBasketException
     */
    public function test_can_validate_basket()
    {
        //given
        $basket = $this->buildBasketSUT();
        $command = new ValidateBasketCommand(
            id:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $response = $this->validateBasket($command);

        //then
        $this->assertTrue($response->isValidated);
        $this->assertEquals($response->isValidated, $basket->status()->value);
        $this->assertNotNull($basket->paymentMethod()->value);
        $this->assertNotNull($basket->currency()->value);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_basket_exception()
    {
        $element = new BasketElement(new FruitReference('Ref01', 1000));
        $element->neededQuantity = new NeededQuantity(3);
        $basket = Basket::create(
            newBasketElement: $element,
            action: BasketAction::ADD_TO_BASKET
        );
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:20,
            paymentMethod: 1,
            currency:2
        );

        $this->expectException(NotFoundBasketException::class);
        $this->validateBasket($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_arguments_exception_when_payment_method_is_invalid()
    {
        $element = new BasketElement(new FruitReference('Ref01', 1000));
        $element->neededQuantity = new NeededQuantity(3);
        $basket = Basket::create($element);
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:$basket->id()->value(),
            paymentMethod: 101,
            currency:1
        );

        $handler = $this->validateBasket();

        $this->expectException(InvalidArgumentsException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_invalid_arguments_exception_when_currency_is_invalid()
    {
        $element1 = new BasketElement(new FruitReference('Ref01',1000));
        $element1->neededQuantity = new NeededQuantity(3);
        $basket = Basket::create($element1);
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:$basket->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:10
        );

        $handler = $this->validateBasket();
        $this->expectException(InvalidArgumentsException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_reference_exception()
    {
        $element1 = new BasketElement(new FruitReference('fakeRef', 101));
        $element1->neededQuantity = new NeededQuantity(3);
        $basket = Basket::create(
            $element1
        );
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:$basket->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:Currency::DOLLAR->value
        );

        $handler = $this->validateBasket();
        $this->expectException(NotFoundFruitReferenceException::class);
        $handler->handle($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_unavailable_fruit_quantity_in_stock_exception()
    {

        $basketElement1 = new BasketElement(new FruitReference('Ref01',1000));
        $basketElement1->neededQuantity = new NeededQuantity(30);

        $basket = Basket::create($basketElement1);
        $basketElement2 = new BasketElement(new FruitReference('Ref02', 2000));
        $basketElement2->neededQuantity = new NeededQuantity(3);

        $basket->addElementToBasket($basketElement2);
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            id:$basket->id()->value(),
            paymentMethod: PaymentMethod::VISA->value,
            currency:Currency::DOLLAR->value
        );

        $handler = $this->validateBasket();

        $this->expectException(UnavailableFruitQuantityException::class);
        $handler->handle($command);
    }

    /**
     * @param ValidateBasketCommand $command
     * @return ValidateBasketResponse
     * @throws NotFoundBasketException
     */
    public function validateBasket(ValidateBasketCommand $command): ValidateBasketResponse
    {
        $handler = new ValidateBasketHandler(
            $this->basketRepository,
            $this->fruitRepository
        );

         return $handler->handle($command);
    }

    /**
     * @return Basket
     * @throws NotFountElementInBasketException
     */
    public function buildBasketSUT(): Basket
    {
        $element = new BasketElement(
            reference: new FruitReference('Ref01',1000)
        );
        $element->neededQuantity = new NeededQuantity(3);
        $basket = Basket::create(
            $element,
            BasketAction::ADD_TO_BASKET
        );
        $this->basketRepository->save($basket);
        return $basket;
    }

}