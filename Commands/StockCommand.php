<?php

namespace Commands;


class StockCommand implements ICommand
{

    public function run($args, $decodedInput = [])
    {
        return 'test';
    }
}