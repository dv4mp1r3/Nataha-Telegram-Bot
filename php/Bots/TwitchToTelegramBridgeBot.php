<?php

declare(strict_types=1);

namespace Bots;

use pbot\Bots\TelegramBot;
use pbot\Commands\CommandListener;
use pbot\Misc\Input\IReader;

class TwitchToTelegramBridgeBot extends TelegramBot
{
    private string $messageText;

    public function __construct(IReader $reader, CommandListener $listener = null)
    {
        $this->messageText = $reader->readAll();
    }

    public function setChatId(int $chatId) : TwitchToTelegramBridgeBot
    {
        $this->chatId = $chatId;
        return $this;
    }

    public function execute(): void
    {
        $url = self::buildFunctionUrl('sendMessage');
        $ch = $this->buildCurlGetTemplate($url, 'POST');
        ob_start();
        $this->sendMessage($this->chatId, $this->messageText);
        $jsonData = json_decode(ob_get_clean(), true);
        if (!$jsonData) {
            return;
        }
        unset($jsonData['method']);
        $jsonData['chat_id'] = strval($this->chatId);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
        $tgAnswer = curl_exec($ch);
        curl_close($ch);
    }

}