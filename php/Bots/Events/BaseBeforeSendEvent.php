<?php

declare(strict_types=1);

namespace Bots\Events;

use pbot\Bots\Events\IEvent;

abstract class BaseBeforeSendEvent implements IEvent
{
    /**
     * @var string
     */
    protected string $eventData;

    /**
     * @param string $data
     */
    public function setEventData(string $data)
    {
        $this->eventData = $data;
    }

    public abstract function run();

}