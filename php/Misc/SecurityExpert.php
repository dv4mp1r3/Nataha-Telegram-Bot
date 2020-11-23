<?php


namespace Misc;


class SecurityExpert
{
    const MESSAGE_GET_OFF = 'Я НЕ БУДУ ТУТ РАБОТАТЬ!';
    const MESSAGE_LOW_DATA = 'Мне нечего сказать. Мало данных';

    protected static array $regExpData = [
        "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
        "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
        //"/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",
    ];

    /**
     * @var string
     */
    protected string $commonMessageText = '';

    /**
     * @param string $lowerRawText
     * @return bool
     */
    public function isStickerTemplateMessage(string $lowerRawText) : bool
    {
        foreach (self::$regExpData as $regExp => $value) {
            if (preg_match($regExp, $lowerRawText)) {
                $this->commonMessageText = $value;
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $lowerRawText
     * @return bool
     */
    public function isBlogTemplateMessage(string $lowerRawText) : bool
    {
        if (preg_match("/сосурити(.*)блог/i", $lowerRawText)) {
            $this->commonMessageText = "НЕТУ";
            return true;
        }
        return false;
    }

    /**
     *
     * @return string
     */
    public function getMessageText() : string
    {
        return $this->commonMessageText;
    }

    /**
     * поиск в тексте сообщения упоминаний бота
     * @param string $lowerRawText
     * @return bool
     */
    public function isReply(string $lowerRawText) : bool
    {
        return preg_match("/сосур(.*)|сосурити|сусурька|сасурян|сосурян/i", $lowerRawText) > 0;
    }

    /**
     * поиск в тексте сообщения шаблона для отправки стикера
     * @param string $loweRawText
     * @return bool
     */
    public function isHaha(string $loweRawText) : bool
    {
        return preg_match("/ахах/i", $loweRawText) > 0;
    }



}