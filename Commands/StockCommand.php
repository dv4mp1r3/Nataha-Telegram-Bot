<?php

declare(strict_types=1);

namespace Commands;


class StockCommand implements ICommand
{

    public function run(array $args, array $decodedInput = []): string
    {
        return 'test';
    }
}