<?php

declare(strict_types=1);

namespace Bots;


class TelegramSecurityExpertBot extends TelegramMarkovBot
{
    const MESSAGE_GET_OFF = 'Я НЕ БУДУ ТУТ РАБОТАТЬ!';
    const MESSAGE_LOW_DATA = 'Мне нечего сказать. Мало данных';

    protected static $regExpData = [
        "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
        "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
        //"/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",
    ];

    public function execute(): void
    {
        parent::execute();
        if ($this->isCommandAlreadyExecuted) {
            return;
        }

        if ($this->chatId != ID_CREATOR && $this->chatId != ID_CHAT) {
            $this->sendMessage($this->chatId, self::MESSAGE_GET_OFF);
            return;
        }

        $lowerRawText = mb_strtolower($this->rawText);

        foreach (self::$regExpData as $regExp => $value) {
            if (preg_match($regExp, $lowerRawText)) {
                $this->sendMessage($this->chatId, $value, 'sendSticker');
                return;
            }
        }

        if (preg_match("/сосурити(.*)блог/i", $lowerRawText)) {
            $this->sendMessage($this->chatId, "НЕТУ");
            return;
        }

        if (
            $this->isReply($this->decodedInput) ||
            preg_match("/сосур(.*)|сосурити|сусурька|сасурян|сосурян/i", $lowerRawText)) {
            $text = $this->markov->generateText(100);
            if (!$text) {
                $text = self::MESSAGE_LOW_DATA;
            }
            $this->sendMessage($this->chatId, $text);
        }
    }
}
