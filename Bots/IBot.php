<?php

declare(strict_types=1);

namespace Bots;

/**
 * Базовый интерфейс для ботов
 * @package Bots
 */
interface IBot
{
    /**
     * @return void
     */
    public function execute(): void;
}
