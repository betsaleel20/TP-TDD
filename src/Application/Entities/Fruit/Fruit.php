<?php

namespace App\Application\Entities\Fruit;

use App\Application\Enums\FruitStatus;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\Quantity;

class Fruit
{

    private FruitStatus $status = FruitStatus::AVAILABLE;

    /**
     * @param Id $id
     * @param FruitReference $reference
     */
    public function __construct(
        private Id             $id,
        private FruitReference $reference
    )
    {
    }

    public static function create(Id $id, FruitReference $reference): self
    {
        return new self($id, $reference);
    }

    /**
     * @return FruitReference
     */
    public function reference(): FruitReference
    {
        return $this->reference;
    }

    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @param FruitStatus $status
     * @return void
     */
    public function changeStatus(FruitStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return FruitStatus
     */
    public function status(): FruitStatus
    {
        return $this->status;
    }


}