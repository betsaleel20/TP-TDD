<?php

namespace Tests\Units\Basket;

use App\Application\Commands\ValidateBasketCommand;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Enums\BasketAction;
use App\Application\Enums\BasketStatus;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\EmptyBasketException;
use App\Application\Exceptions\InvalidArgumentsException;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFountElementInBasketException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\ValidateBasketResponse;
use App\Application\Services\GetFruitsToSoldService;
use App\Application\UseCases\Basket\ValidateBasketHandler;
use App\Application\ValueObjects\BasketElement;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Quantity;
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
            basketId:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $response = $this->validateBasket($command);

        //then
        $this->assertTrue( $response->isValidated );
        $this->assertNotNull( $response->orderId );
        $this->assertEmpty($basket->basketElements());
        $this->assertEquals(BasketStatus::IS_DESTROYED, $basket->status());
        $this->assertEquals( $response->isValidated, $basket->status()->value );
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     */
    public function test_can_throw_not_found_exception_when_basket_id_is_incorrect()
    {
        //given
        $incorrectId = 'somethingWrong';
        $command = new ValidateBasketCommand(
            basketId:             $incorrectId,
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When && Then
        $this->expectException(NotFoundBasketException::class);
        $this->validateBasket($command);
    }

    /**
     * @return void
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_invalid_argument_exception_when_payment_method_is_not_valid()
    {
        //given
        $basket = $this->buildBasketSUT();
        $incorrectPaymentMethod = 101;
        $command = new ValidateBasketCommand(
            basketId:             $basket->id()->value(),
            paymentMethod:  $incorrectPaymentMethod,
            currency:       Currency::DOLLAR->value
        );

        //When
        $this->expectException(InvalidArgumentsException::class);
        $this->validateBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_invalid_argument_exception_when_currency_is_not_valid()
    {
        //given
        $basket = $this->buildBasketSUT();
        $incorrectCurrency = 101;
        $command = new ValidateBasketCommand(
            basketId:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       $incorrectCurrency
        );

        //When
        $this->expectException(InvalidArgumentsException::class);
        $this->validateBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_empty_basket_exception()
    {
        //given
        $basket = $this->buildBasketSUT();
        $basket->makeBasketEmpty();
        $command = new ValidateBasketCommand(
            basketId:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $this->expectException(EmptyBasketException::class);
        $this->validateBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_unavailable_fruit_quantity_exception_when_validating_basket()
    {
        //given
        $basket = $this->buildBasketSUT();

        $newElement = new BasketElement( reference: new FruitReference('Ref02',2000) );
        $newElement->neededQuantity = new Quantity(10);
        $basket->addElementToBasket($newElement);
        $command = new ValidateBasketCommand(
            basketId:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $this->expectException(UnavailableFruitQuantityException::class);
        $this->validateBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_throw_not_found_fruit_reference_exception_when_validating_basket()
    {
        //given
        $notAvailableReference = 'noMoreAvailable';
        $element = new BasketElement( reference: new FruitReference($notAvailableReference,1000) );
        $element->neededQuantity = new Quantity(3);
        $basket = Basket::create(
            $element,
            BasketAction::ADD_TO_BASKET
        );
        $this->basketRepository->save($basket);
        $command = new ValidateBasketCommand(
            basketId:             $basket->id()->value(),
            paymentMethod:  PaymentMethod::VISA->value,
            currency:       Currency::DOLLAR->value
        );

        //When
        $this->expectException(NotFoundFruitReferenceException::class);
        $this->validateBasket($command);
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFountElementInBasketException
     */
    public function test_can_apply_discount_on_basket()
    {
        $basket = $this->buildBasketSUT();
        $command = new ValidateBasketCommand(
            basketId:$basket->id()->value(),
            paymentMethod: PaymentMethod::MASTERCARD->value,currency: Currency::DOLLAR->value
        );
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
            $this->fruitRepository,
            new GetFruitsToSoldService($this->fruitRepository)
        );

         return $handler->handle($command);
    }

    /**
     * @return Basket
     * @throws NotFountElementInBasketException|NotFoundBasketException
     */
    public function buildBasketSUT(): Basket
    {
        $element1 = new BasketElement( reference: new FruitReference('Ref01',1000) );
        $element1->neededQuantity = new Quantity(3);
        $basket = Basket::create(
            $element1,
            BasketAction::ADD_TO_BASKET
        );

        $element2 = new BasketElement( reference: new FruitReference('Ref02',2000) );
        $element2->neededQuantity = new Quantity(4);
        $basket->addElementToBasket($element2);

        $this->basketRepository->save($basket);
        return $basket;
    }

}