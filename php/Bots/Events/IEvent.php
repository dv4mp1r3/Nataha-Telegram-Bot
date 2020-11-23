<?php

declare(strict_types=1);

namespace Bots\Events;

interface IEvent
{
    public function run();
}