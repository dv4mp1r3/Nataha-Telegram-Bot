<?php

namespace Bots;


use Misc\SecurityExpert;

class TelegramSecurityExpertBot extends TelegramMarkovBot
{
    const MESSAGE_GET_OFF = 'Я НЕ БУДУ ТУТ РАБОТАТЬ!';
    const MESSAGE_LOW_DATA = 'Мне нечего сказать. Мало данных';

    protected static $regExpData = [
        "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
        "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
        //"/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",
    ];

    public function execute()
    {
        parent::execute();
        if ($this->isCommandAlreadyExecuted) {
            return;
        }
        $securityExpert = new SecurityExpert();

        if ($this->chatId != ID_CREATOR && $this->chatId != ID_CHAT) {
            $this->sendMessage($this->chatId, SecurityExpert::MESSAGE_GET_OFF);
            return;
        }

        $lowerRawText = mb_strtolower($this->rawText);
        $isSecurityReply = $securityExpert->isReply($lowerRawText);
        if ($securityExpert->isStickerTemplateMessage($lowerRawText))
        {
            $this->sendMessage($this->chatId, $securityExpert->getMessageText(), 'sendSticker');
        }
        else if ($isSecurityReply && $securityExpert->isBlogTemplateMessage($lowerRawText)) {
            $this->sendMessage($this->chatId, "НЕТУ");
        }
        else if ($this->isReply($this->decodedInput) || $isSecurityReply)
        {
            $text = $this->markov->generateText(100);
            if (!$text) {
                $text = SecurityExpert::MESSAGE_LOW_DATA;
            }
            $this->sendMessage($this->chatId, $text);
        }
    }
}
