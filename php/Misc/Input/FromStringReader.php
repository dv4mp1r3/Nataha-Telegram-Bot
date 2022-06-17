<?php

declare(strict_types=1);

namespace Misc\Input;

use pbot\Misc\Input\IReader;

class FromStringReader implements IReader
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function readAll(): string
    {
        return $this->value;
    }
}