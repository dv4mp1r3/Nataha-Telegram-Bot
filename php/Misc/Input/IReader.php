<?php

declare(strict_types=1);

namespace Misc\Input;

interface IReader
{
    public function readAll() : string;
}