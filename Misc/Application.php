<?php

declare(strict_types=1);

namespace Misc;

use Bots\IBot;

class Application
{

    /**
     * @var bool
     */
    protected bool $isDebug = false;

    /**
     * @var IBot
     */
    protected IBot $bot;

    protected Logger $logger;

    /**
     * Application constructor.
     * @param IBot $bot
     * @param bool $isDebug
     * @param Logger $l
     */
    public function __construct(IBot $bot, bool $isDebug = false, Logger $l)
    {
        $this->bot = $bot;
        $this->isDebug = $isDebug;
        $this->logger = $l;
        set_error_handler([$l, Logger::ERROR_HANDLER_FUNCTION]);
    }

    public function run() : void
    {
        try {
            $this->bot->execute();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}