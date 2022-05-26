<?php

declare(strict_types=1);

namespace Commands;

use pbot\Commands\ICommand;

class StockCommand implements ICommand
{

    public function run(array $args, array $decodedInput = []): string
    {
        return 'test';
    }
}