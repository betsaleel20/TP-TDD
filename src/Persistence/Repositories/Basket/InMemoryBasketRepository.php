<?php

namespace App\Persistence\Repositories\Basket;

use App\Application\Entities\Basket\Basket;
use App\Application\Entities\Basket\BasketRepository;
use App\Application\ValueObjects\Id;

class InMemoryBasketRepository implements BasketRepository
{

    private array $baskets = [];

    /**
     * @param Basket $basket
     * @return void
     */
    public function save(Basket $basket): void
    {
        $inMemoryBasket = $this->byId($basket->id());
        if(!$inMemoryBasket){
            $this->baskets[] = $basket;
            return;
        }
        $this->delete( $inMemoryBasket );

        $this->baskets[] = $basket;
    }

    public function byId(Id $basketId): ?Basket
    {
        $result = array_values(array_filter(
                $this->baskets, fn(Basket $b) => $b->id()->value() === $basketId->value())
        );
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * @return Basket[]
     */
    public function allBaskets(): array
    {
        return $this->baskets;
    }

    public function delete(Basket $inMemoryBaskets): void
    {
        $this->baskets = array_values(array_filter(
            $this->allBaskets(),
            fn(Basket $b)=>$b->id()->value() !== $inMemoryBaskets->id()->value()
        ));
    }
}