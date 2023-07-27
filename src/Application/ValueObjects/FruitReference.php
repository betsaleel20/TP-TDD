<?php

namespace App\Application\ValueObjects;

use App\Application\Exceptions\InvalidCommandException;

readonly class FruitReference
{

    private float $price;

    /**
     * @param string $reference
     * @param float $price
     */
    public function __construct(private string $reference, float $price = 1000.0)
    {
        $this->price = $price;
        $this->validate();
    }

    /**
     * @return void
     * @throws InvalidCommandException
     */
    private function validate(): void
    {
        if($this->price <=0){
            throw new InvalidCommandException("Le prix doit etre supérieur à zéro !");
        }
        if ( !$this->reference ) {
            throw new InvalidCommandException("La référence est invalide !");
        }
    }

    /**
     * @return string
     */
    public function referenceValue(): string
    {
        return $this->reference;
    }

    /**
     * @return float
     */
    public function price():float
    {
        return $this->price;
    }

}