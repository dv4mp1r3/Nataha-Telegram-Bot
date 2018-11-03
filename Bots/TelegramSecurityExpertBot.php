<?php

namespace Bots;


class TelegramSecurityExpertBot extends TelegramMarkovBot
{
    const MESSAGE_GET_OFF = 'Я НЕ БУДУ ТУТ РАБОТАТЬ!';
    const MESSAGE_LOW_DATA = 'Мне нечего сказать. Мало данных';

    public function execute()
    {
        if ($this->chatId != ID_CREATOR && $this->chatId != ID_CHAT) {
            $this->sendMessage($this->chatId, self::MESSAGE_GET_OFF);
            return;
        }

        $botName = mb_strtolower($this->rawText);

        foreach (self::$regExpData as $regExp => $value) {
            if (preg_match($regExp, $botName)) {
                $this->sendMessage($this->chatId, $value, 'sendSticker');
                return;
            }
        }

        if (preg_match("/сосурити(.*)блог/i", $botName) == true) {
            sendMessage($this->chatId, "НЕТУ");
            return;
        }

        $chain = $this->getChain();
        if (
            $this->isReply($this->decodedInput) ||
            preg_match("/сосур(.*)|сосурити|сусурька|сасурян|сосурян/i", $botName) == true) {

            $text = $this->generateText(100, $chain);
            if (!$text) {
                $text = self::MESSAGE_LOW_DATA;
            }
            $this->sendMessage($this->chatId, $text);
        } else {
            $this->updateDataBase($chain);
        }
    }
}