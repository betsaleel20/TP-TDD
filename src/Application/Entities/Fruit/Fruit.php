<?php

namespace App\Application\Entities\Fruit;

use App\Application\Enums\FruitStatus;
use App\Application\ValueObjects\FruitReference;
use App\Application\ValueObjects\Id;
use App\Application\ValueObjects\Quantity;

class Fruit
{
    /**
     * @param Id $id
     * @param FruitReference $reference
     * @param FruitStatus $status
     */
    private function __construct(
        private readonly Id    $id,
        private readonly FruitReference $reference,
        private FruitStatus $status
    )
    {
    }

    public static function create(Id $id, FruitReference $reference, ?FruitStatus $status): self
    {
        isset($status) ? : $status = FruitStatus::AVAILABLE;
        return new self($id, $reference,$status);
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