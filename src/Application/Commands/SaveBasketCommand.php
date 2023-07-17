<?php

namespace App\Application\Commands;

use App\Application\Enums\OrderAction;
use App\Application\Exceptions\InvalidCommandException;
use App\Application\ValueObjects\NeededQuantity;

class SaveBasketCommand
{

    public ?string $orderId;
    public ?int $orderedQuantity;

    /**
     * @param string $fruitRef
     * @param int $action
     */
    private function __construct(
        readonly public string $fruitRef,
        readonly public int $action
    )
    {
        $this->orderedQuantity = null;
        $this->orderId = null;
    }

    public static function create( string $fruitRef, int $action, ?int $orderedQuantity = null):self
    {
        $saveOrderCommand = new self( fruitRef:$fruitRef,action: $action);
        $saveOrderCommand->orderedQuantity = $orderedQuantity;
        $saveOrderCommand->validate();
        return $saveOrderCommand;
    }

    private function validate():void
    {
        if( !$this->orderedQuantity && $this->action !== OrderAction::REMOVE_FROM_ORDER->value )
        {
            throw new InvalidCommandException(
                "La quantité commandée doit etre supérieure à zéro 
                en cas d'ajout ou de modification d'une commande !"
            );
        }
    }

}