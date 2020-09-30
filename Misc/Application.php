<?php

namespace Misc;

use Bots\IBot;

class Application
{

    /**
     * @var bool
     */
    protected $isDebug = false;

    /**
     * @var IBot
     */
    protected $bot;

    protected $logger;

    /**
     * Application constructor.
     * @param IBot $bot
     * @param bool $isDebug
     * @param Logger $l
     */
    public function __construct($bot, $isDebug = false, $l)
    {
        $this->bot = $bot;
        $this->isDebug = $isDebug;
        $this->logger = $l;
        set_error_handler([$l, Logger::ERROR_HANDLER_FUNCTION]);
    }

    public function run()
    {
        try {
            $this->bot->execute();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}