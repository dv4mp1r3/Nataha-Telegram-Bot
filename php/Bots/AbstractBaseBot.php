<?php

declare(strict_types=1);

namespace Bots;

use Commands\CommandListener;

/**
 * Базовый класс для ботов
 * @package Bots
 */
abstract class AbstractBaseBot implements IBot
{
    /**
     * @var CommandListener
     */
    protected CommandListener $commandListener;

    /**
     * AbstractBaseBot constructor.
     * @param CommandListener|null $listener
     */
    public function __construct(CommandListener $listener = null)
    {
        $this->setCommandListener($listener);
    }

    /**
     * @param CommandListener $listener
     */
    public function setCommandListener(CommandListener $listener) : void
    {
        $this->commandListener = $listener;
    }
}