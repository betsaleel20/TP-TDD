<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\PaymentMethod;
use App\Application\Exceptions\InvalidFruitReferenceException;
use App\Application\Exceptions\UnavailableFruitQuantity;
use App\Application\Responses\ValidateOrderResponse;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

class ValidateOrderHandler
{

    public FruitRepository $fruitRepository;
    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(public OrderRepository $orderRepository)
    {
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    public function handle(ValidateOrderCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();
        $order = $this->orderRepository->byId(new Id($command->id));
        $this->validatePaymentMethodOrThrowInvalidArgumentException($command->paymentMethod);
        $this->validateCurrencyOrThrowInvalidArgumentException($command->currency);
        $orderElements = $order->orderElements();
        $numberOfOrderedElements = count($orderElements);
        for($i = 0; $i < $numberOfOrderedElements; $i++){
            $this->validateFruitReferenceOrThrowInvalidFruitReferenceException($orderElements[$i]->reference());
        }



        $response->isValidated = true;
        $response->orderId = $order->id()->value();
        return $response;
    }

    /**
     * @param int $paymentMethod
     * @return void
     */
    public function validatePaymentMethodOrThrowInvalidArgumentException(int $paymentMethod): void
    {
        PaymentMethod::in($paymentMethod);
    }

    /**
     * @param int $currency
     * @return void
     */
    public function validateCurrencyOrThrowInvalidArgumentException(int $currency): void
    {
        Currency::in($currency);
    }

    public function validateFruitReferenceOrThrowInvalidFruitReferenceException(FruitReference $fruitReference): void
    {
        if(is_null($this->fruitRepository->byReference($fruitReference))){
            throw new InvalidFruitReferenceException("La reference que vous avez renseigné est invalide");
        }
    }

}