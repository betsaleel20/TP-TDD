<?php

namespace App\Application\Commands;

use App\Application\Enums\BasketAction;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\ValueObjects\Quantity;

class SaveBasketCommand
{

    public ?string $basketId;
    public ?int $neededQuantity;
    public ?float $price;

    /**
     * @param string $fruitRef
     * @param int $action
     */
    private function __construct(
        readonly public string $fruitRef,
        readonly public int $action
    )
    {
        $this->price = null;
        $this->neededQuantity = null;
        $this->basketId = null;
    }

    public static function create( string $fruitRef,int $action, ?int $neededQuantity = null):self
    {
        $saveBasketCommand = new self( fruitRef:$fruitRef,action: $action);
        $saveBasketCommand->neededQuantity = $neededQuantity;
        $saveBasketCommand->validate();
        return $saveBasketCommand;
    }

    private function validate():void
    {
        if( !$this->neededQuantity && $this->action !== BasketAction::REMOVE_FROM_BASKET->value )
        {
            throw new InvalidCommandException(
                "La quantité commandée doit etre supérieure à zéro 
                en cas d'ajout ou de modification d'une commande !"
            );
        }
    }

}