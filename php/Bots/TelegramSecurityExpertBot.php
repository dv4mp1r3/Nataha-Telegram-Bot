<?php

declare(strict_types=1);

namespace Bots;


use Misc\SecurityExpert;

class TelegramSecurityExpertBot extends TelegramMarkovBot
{
    const MESSAGE_GET_OFF = 'Я НЕ БУДУ ТУТ РАБОТАТЬ!';
    const MESSAGE_LOW_DATA = 'Мне нечего сказать. Мало данных';

    protected static array $regExpData = [
        "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
        "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
        //"/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",
    ];

    private int $maxWordsCount;

    public function setMaxWordsCount(int $mwc): self
    {
        $this->maxWordsCount = $mwc;
        return $this;
    }

    public function execute(): void
    {
        parent::execute();
        if ($this->isCommandAlreadyExecuted) {
            return;
        }
        $securityExpert = new SecurityExpert();
        if ($this->getChatId() != ID_CREATOR && $this->getChatId() != ID_CHAT) {
            $this->sendMessage($this->getChatId(), SecurityExpert::MESSAGE_GET_OFF);
            return;
        }

        $lowerRawText = mb_strtolower($this->getRawText());
        $isSecurityReply = $securityExpert->isReply($lowerRawText);
        if ($securityExpert->isStickerTemplateMessage($lowerRawText))
        {
            $this->sendMessage($this->getChatId(), $securityExpert->getMessageText(), 'sendSticker');
        }
        else if ($isSecurityReply && $securityExpert->isBlogTemplateMessage($lowerRawText)) {
            $this->sendMessage($this->getChatId(), "НЕТУ");
        }
        else if ($this->isReply($this->getDecodedInput()) || $isSecurityReply)
        {
            $text = $this->markov->generateText($this->maxWordsCount);
            if (!$text) {
                $text = SecurityExpert::MESSAGE_LOW_DATA;
            }
            $this->sendMessage($this->getChatId(), $text);
        }
    }
}
