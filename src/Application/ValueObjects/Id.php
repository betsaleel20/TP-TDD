<?php

namespace App\Application\ValueObjects;

readonly class Id
{
    public function __construct(private string $value)
    {
    }
    public function value(): string
    {
        return $this->value;
    }
}