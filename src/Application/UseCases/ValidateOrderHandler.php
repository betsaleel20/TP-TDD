<?php

namespace App\Application\UseCases;

use App\Application\Commands\ValidateOrderCommand;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\Currency;
use App\Application\Enums\OrderStatus;
use App\Application\Enums\PaymentMethod;
use App\Application\Responses\ValidateOrderResponse;
use App\Application\Services\ChangeFruitsStatusOfValidatedOrderToSoldService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\ValueObjects\Id;

class ValidateOrderHandler
{
    private FruitRepository $fruitRepository;

    /**
     * @param OrderRepository $orderRepository
     * @param FruitRepository $fruitRepository
     */
    public function __construct(
        private OrderRepository $orderRepository,
        private VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStockService,
        private ChangeFruitsStatusOfValidatedOrderToSoldService $ChangeFruitsStatusToSoldForValidatedOrder
    )
    {
    }

    public function handle(ValidateOrderCommand $command): ValidateOrderResponse
    {
        $response = new ValidateOrderResponse();
        $order = $this->orderRepository->byId(new Id($command->id));
        $order->changePaymentMethod(PaymentMethod::in($command->paymentMethod));
        $order->changeCurrency(Currency::in($command->currency));

        $orderedElements = $order->orderElements();
        $numberOfOrderedElements = count($orderedElements);
        for($i = 0; $i < $numberOfOrderedElements; $i++){
            $this->verifyIfThereIsEnoughFruitInStockService->execute($orderedElements[$i]);
        }

        $orderElements = $order->orderElements();
        while ($i < count($orderElements)){
            $this->ChangeFruitsStatusToSoldForValidatedOrder->execute($orderElements[$i]);
            $i++;
        }

        $response->isValidated = true;
        $response->paymentMethod = $order->paymentMethod()->value;
        $response->currency = $order->currency()->value;
        $order->changeStatus(OrderStatus::IS_VALIDATED);
        $response->orderId = $order->id()->value();
        return $response;
    }


}