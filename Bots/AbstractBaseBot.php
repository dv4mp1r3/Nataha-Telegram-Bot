<?php

declare(strict_types=1);

namespace Bots;

use Commands\CommandListener;

abstract class AbstractBaseBot implements IBot
{
    /**
     * @var CommandListener
     */
    protected $commandListener;

    public function __construct(CommandListener $listener = null)
    {
        $this->setCommandListener($listener);
    }

    public function setCommandListener(CommandListener $listener) : void
    {
        $this->commandListener = $listener;
    }
}