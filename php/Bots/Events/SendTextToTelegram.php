<?php

declare(strict_types=1);

namespace Bots\Events;

use Bots\TwitchToTelegramBridgeBot;
use Misc\Input\FromStringReader;

class SendTextToTelegram extends BaseBeforeSendEvent
{

    private int $chatId;

    public function __construct($chatId)
    {
        $this->chatId = $chatId;
    }

    public function run()
    {
       $bot = new TwitchToTelegramBridgeBot(new FromStringReader($this->eventData));
       $bot->setChatId($this->chatId)->execute();
    }
}