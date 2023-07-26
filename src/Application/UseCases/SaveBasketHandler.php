<?php

namespace App\Application\UseCases;

use App\Application\Commands\SaveBasketCommand;
use App\Application\Entities\Fruit\Fruit;
use App\Application\Entities\Fruit\FruitRepository;
use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\Enums\OrderAction;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\Exceptions\NotFoundElementException;
use App\Application\Exceptions\NotFoundFruitReferenceException;
use App\Application\Exceptions\NotFoundBasketException;
use App\Application\Exceptions\NotFountOrderElementException;
use App\Application\Exceptions\UnavailableFruitQuantityException;
use App\Application\Responses\SaveOrderResponse;
use App\Application\Services\GetFruitByReferenceService;
use App\Application\Services\VerifyIfThereIsEnoughFruitInStockService;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\NeededQuantity;
use App\Application\ValueObjects\BasketElement;
use App\Persistence\Repositories\Fruit\InMemoryFruitRepository;

readonly class SaveBasketHandler
{


    private FruitRepository $fruitRepository;

    public function __construct(
        private BasketRepository                         $repository,
        private GetFruitByReferenceService               $verifyIfFruitReferenceExistsOrThrowNotFoundException,
        private VerifyIfThereIsEnoughFruitInStockService $verifyIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException
    )
    {
        $this->fruitRepository = new InMemoryFruitRepository();
    }

    /**
     * @throws NotFoundBasketException
     * @throws NotFoundFruitReferenceException
     * @throws NotFountOrderElementException
     */
    public function handle(SaveBasketCommand $command): SaveOrderResponse
    {
        $response = new SaveOrderResponse();

        $orderId = $command->orderId ? new Id($command->orderId) : null;
        $fruitRef = new FruitReference($command->fruitRef);

        if($command->action !== OrderAction::REMOVE_FROM_ORDER->value ) {
            $this->verifyIfFruitReferenceExistsOrThrowNotFoundException->execute($fruitRef);
            $orderElement = new BasketElement(
                reference: $fruitRef,
                orderedQuantity: new NeededQuantity($command->orderedQuantity)
            );

            $this->checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException($orderElement);

            if (!$orderId) {
                Basket::create(
                    basketElement: $orderElement,
                    id: $orderId
                );
            }
            $order = $this->getOrderOrThrowNotFoundException($orderId);
            $action = OrderAction::in($command->action);
            $order->updateBasketElement($orderElement, $action);
        }
        else{
            $order = $this->getOrderOrThrowNotFoundException($orderId);
            OrderAction::in($command->action);
            if(!$order->checkElementExistence($fruitRef)){
                throw new NotFoundElementException('Le fruit que vous souhaitez supprimer 
                n\'existe pas dans votre panier');
            }

            $order->removeElementFromBasket($fruitRef);
        }

        $this->repository->save($order);

        $response->isSaved = true;
        $response->orderId = $order->id()->value();
        $response->orderStatus = $order->status()->value;

        return $response;
    }

    /**
     * @param Id|null $orderId
     * @return Basket
     * @throws NotFoundBasketException
     */
    private function getOrderOrThrowNotFoundException(?Id $orderId): Basket
    {
        $order = $this->repository->byId($orderId);
        if (!$order) {
            throw new NotFoundBasketException("Cette commande n'existe pas !");
        }

        return $order;
    }

    /**
     * @param BasketElement $orderElement
     * @return void
     */
    private function checkIfFruitsAreAvailableOrThrowUnavailableFruitQuantityException(BasketElement $orderElement): void
    {
        $state = $this->verifyIfThereIsEnoughFruitInStockOrThrowUnavailableFruitQuantityException->execute(
            $orderElement->reference(),
            $orderElement->quantity()
        );
        if(!$state)
        {
            throw new UnavailableFruitQuantityException('Les fruits de cette reference sont en rupture de stock');
        }
    }


}