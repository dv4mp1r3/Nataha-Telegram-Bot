<?php
namespace Bots;

class TelegramMarkovBot extends TelegramBot
{
    protected static $filterRegEx = [
        "urlFilter" => "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
        "punctiationFilter" => "/(?<!\w)[.,!]/",
        "newlineFilter" => "/\r|\n/",
    ];
    protected static $regExpData = [
        "/ресеп(.*)сука|ресеп(.*)тупая|ресеп(.*)несешь/i" => "CAADAgADCQADaJpdDDa9pygUaeHvAg",
        "/ахах/i" => "CAADAgADnQADaJpdDK2h3LaVb7oGAg",
        "/php|пых/i" => "CAADAgADEwADmqwRGPffQIaMmNCbAg",
    ];
    
    protected static $writeHumanReadable = false;

    /**
     * Генерация текста сообщения
     * @param integer $maxWords
     * @param array $data
     * @return string
     */
    protected function generateText($maxWords, $data)
    {
        $customTextProcesingFunctionName = 'customTextProcessing';
        if (empty($data)) {
            throw new \Exception('Bad data format');
        }
        $out = array_rand($data['chain']); // initial word
        while ($out = $this->weighAndSelect($data['chain'][$out])) {
            $text[] = base64_decode($out);
            if (count($text) > $maxWords) {
                break;
            }
        }

        if (function_exists($customTextProcesingFunctionName)) {
            return customTextProcessing(implode(" ", $text));
        }

        return $text;
    }

    /**
     * генерация/обновление цепи
     * @param string $message
     * @param array $data
     * @return boolean|int
     */
    protected function train($message, $data)
    {
        $array = explode(" ", $message);

        foreach ($array as $num => $val) {
            $val = base64_encode($val);
            if (!$val) {
                continue;
            }
            // if there is already a block for this word, keep it, otherwise create one   
            $commit = (isset($data['chain'][$val]) ? $data['chain'][$val] : array());      
            if (empty($array[$num + 1])) {
                // if this word is EOL, continue to the next word
                continue;
            }
            // the next word after the one currently selected
            $next = $array[$num + 1]; 
            $next = base64_encode($next);
            if (isset($commit[$next])) {
                // if the word already exists, increase the weight
                $commit[$next] ++; 
            } else {
                // otherwise save the word with a weight of 1
                $commit[$next] = 1; 
            }
            // commit to the chain
            $data['chain'][$val] = $commit; 
        }
        return $data;
    }

    /**
     * 
     * @param type $block
     * @return boolean
     */
    function weighAndSelect($block)
    {
        if (empty($block)) {
            return false;
        }

        foreach ($block as $key => $weight) {
            for ($i = 1; $i <= $weight; $i++) {
                $tmp[] = $key;
            }
        }

        $rand = array_rand($tmp);
        return $tmp[$rand];
    }

    public function execute()
    {
        parent::execute();

        if ((string) $this->chatId !== AVAILABLE_CHAT_ID) {
            $this->sendMessage($this->chatId, "Я НЕ БУДУ ТУТ РАБОТАТЬ!");
        }

        $botName = mb_strtolower($this->rawText);

        foreach (self::$regExpData as $regExp => $value) {
            if (preg_match($regExp, $botName)) {
                $this->sendMessage($this->chatId, $value, 'sendSticker');
                return;
            }
        }

        if (preg_match("/нат(.*)блог/i", $botName) == true) {
            sendMessage($this->chatId, "НЕТУ");
            return;
        }

        $fp = null;
        $fileName = CONFIG_PATH;
        $tryCount = 0;
        $chain = json_decode(file_get_contents($fileName), true);
        while ($chain == false) {
            if ($tryCount === MAX_DB_READ_TRY) {
                throw new \Exception("Can't get file content for $fileName");
            }
            $tryCount++;
            $chain = json_decode(file_get_contents($fileName), true);
            usleep(FLOCK_SLEEP_INTERVAL);
        }

        if (
            $this->isReply($this->decodedInput) ||
            preg_match("/ресеп(.*)|ресепшен|ресептион|ресепшин|ресепшинъ|ресепшенъ/i", $botName) == true) {

            $text = $this->generateText(100, $chain);
            if (!$text) {
                $text = "Мне нечего сказать. Мало данных";
            }
            $this->sendMessage($this->chatId, $text);
        } else {
            $preparedText = strtolower($this->rawText);
            foreach (self::$filterRegEx as $pattern) {
                $preparedText = preg_replace($pattern, " ", $preparedText);
            }
            $putData = "false";
            if (!empty($preparedText)) {
                $putData = json_encode($this->train($preparedText, $chain));
                if ($putData !== "false") {
                    file_put_contents($fileName, $putData, LOCK_EX);
                    if (self::$writeHumanReadable) {
                        file_put_contents($this->chatId . ".json.txt", print_r($chain, true), LOCK_EX);
                    }
                } else {
                    if (defined('IS_DEBUG') && IS_DEBUG) {
                        $this->sendMessage($this->chatId, "ЧТО-ПОШЛО НЕ ТАК!!!! ДАМП ПОСЛЕДНЕГО СООБЩЕНИЯ\n:" . json_encode($this->decodedInput));
                    }
                }
            }
        }
    }
}
