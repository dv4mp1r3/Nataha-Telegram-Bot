<?php

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
    protected $commandListener;

    /**
     * AbstractBaseBot constructor.
     * @param CommandListener|null $listener
     */
    public function __construct($listener = null)
    {
        $this->setCommandListener($listener);
    }

    /**
     * @param CommandListener $listener
     */
    public function setCommandListener($listener)
    {
        $this->commandListener = $listener;
    }
}