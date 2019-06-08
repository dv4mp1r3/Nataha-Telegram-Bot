<?php

declare(strict_types=1);

namespace Bots;

interface IBot
{
    /**
     * @return void
     */
    public function execute(): void;
}
