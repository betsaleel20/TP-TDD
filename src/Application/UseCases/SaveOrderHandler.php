<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveOrderCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Order\Order;
use App\Application\Entities\Order\OrderRepository;
use App\Application\Enums\OrderAction;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundOrderException;
use App\Application\Exceptions\NotFountOrderElementException;
use App\Application\Exceptions\UnavailableFruitQuantity;
use App\Application\Responses\SaveOrderResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\OrderedQuantity;
use App\Application\ValueObjects\OrderElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

readonly class SaveOrderHandler
{


    private FruitRepository $fruitRepository;

    public function __construct(
        private OrderRepository            $repository,
        private GetFruitByReferenceService $verifyIfFruitReferenceExistsOrThrowNotFoundException,
        private VerifyIfThereIsEnoughFruitInStockService $verifyIfThereEnoughFruitInStockOrThrowUnavailableFruitQuantityException
    )
    {
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundOrderException
     * @throws NotFoundFruitReferenceException
     * @throws NotFountOrderElementException
     */
    public function handle(SaveOrderCommand $command): SaveOrderResponse
    {
        $response = new SaveOrderResponse();

        $orderId = $command->orderId ? new Id($command->orderId) : null;
        $fruitRef = new FruitReference($command->fruitRef);

        $orderElement = new OrderElement(
            reference: $fruitRef,
            orderedQuantity: new OrderedQuantity($command->orderedQuantity)
        );

        $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);
        $this->verifyIfThereEnoughFruitInStockOrThrowUnavailableFruitQuantityException->execute($orderElement);

        if (!$orderId) {
            $order = Order::create(
                orderElement: $orderElement,
                id: $orderId
            );
        } else {
            $action = OrderAction::in($command->action);
            $order = $this->getOrderOrThrowNotFoundException($orderId);
            $order->updateOrderElement($orderElement, $action);
        }

        $this->repository->save($order);

        $response->isSaved = true;
        $response->orderId = $order->id()->value();
        $response->orderStatus = $order->status()->value;

        return $response;
    }

    /**
     * @param Id|null $orderId
     * @return Order
     * @throws NotFoundOrderException
     */
    private function getOrderOrThrowNotFoundException(?Id $orderId): Order
    {
        $order = $this->repository->byId($orderId);
        if (!$order) {
            throw new NotFoundOrderException("Cette commande n'existe pas !");
        }

        return $order;
    }

}