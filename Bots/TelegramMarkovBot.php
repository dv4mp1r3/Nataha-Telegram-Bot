<?php

declare(strict_types=1);

namespace Bots;

class TelegramMarkovBot extends TelegramBot
{
    protected static $filterRegEx = [
        "urlFilter" => "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
        "punctiationFilter" => "/(?<!\w)[.,!]/",
        "newlineFilter" => "/\r|\n/",
    ];

    const ARRAY_KEY_CHAIN = 'chain';

    protected static $writeHumanReadable = false;

    /**
     * Генерация текста сообщения
     * @param integer $maxWords
     * @param array $data
     * @return string
     * @throws \Exception
     */
    protected function generateText(int $maxWords, array $data): string
    {
        $text = [];
        $customTextProcesingFunctionName = 'customTextProcessing';
        if (empty($data)) {
            throw new \Exception('Bad data format');
        }
        $out = array_rand($data[self::ARRAY_KEY_CHAIN]); // initial word
        while (true) {
            $tmp = $data[self::ARRAY_KEY_CHAIN][$out];
            if (is_null($tmp)) {
                break;
            }
            $out = $this->weighAndSelect($tmp);
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
     * @return array
     */
    protected function train(string $message, array $data): array
    {
        $array = explode(" ", $message);

        foreach ($array as $num => $val) {
            $val = base64_encode($val);
            if (!$val) {
                continue;
            }
            // if there is already a block for this word, keep it, otherwise create one   
            $commit = (isset($data[self::ARRAY_KEY_CHAIN][$val]) ? $data[self::ARRAY_KEY_CHAIN][$val] : array());
            if (empty($array[$num + 1])) {
                // if this word is EOL, continue to the next word
                continue;
            }
            // the next word after the one currently selected
            $next = $array[$num + 1];
            $next = base64_encode($next);
            if (isset($commit[$next])) {
                // if the word already exists, increase the weight
                $commit[$next]++;
            } else {
                // otherwise save the word with a weight of 1
                $commit[$next] = 1;
            }
            // commit to the chain
            $data[self::ARRAY_KEY_CHAIN][$val] = $commit;
        }
        return $data;
    }

    /**
     *
     * @param array $block
     * @return boolean
     */
    protected function weighAndSelect(array $block)
    {
        if (empty($block)) {
            return false;
        }

        $tmp = [];

        foreach ($block as $key => $weight) {
            for ($i = 1; $i <= $weight; $i++) {
                $tmp[] = $key;
            }
        }

        $rand = array_rand($tmp);
        return $tmp[$rand];
    }

    /**
     * Обновление файла с цепью
     * @param array $chain
     * @throws \Exception
     */
    protected function updateDataBase(array $chain): void
    {
        $preparedText = strtolower($this->rawText);
        foreach (self::$filterRegEx as $pattern) {
            $preparedText = preg_replace($pattern, " ", $preparedText);
        }
        if (!empty($preparedText)) {
            $putData = json_encode($this->train($preparedText, $chain));
            if ($putData !== "false") {
                file_put_contents(CONFIG_PATH, $putData, LOCK_EX);
                if (self::$writeHumanReadable) {
                    file_put_contents($this->chatId . ".json.txt", print_r($chain, true), LOCK_EX);
                }
            } else {
                throw new \Exception('$putData is false (file_put_contents error)');
            }
        }
    }

    /**
     * Чтение и возврат цепи в виде ассоциированного массива
     * @return array
     * @throws \Exception
     */
    protected function getChain(): array
    {
        $tryCount = 0;
        $chain = json_decode(file_get_contents(CONFIG_PATH), true);
        while (!$chain) {
            if ($tryCount === MAX_DB_READ_TRY) {
                throw new \Exception("Can't get file content for " . CONFIG_PATH);
            }
            $tryCount++;
            $chain = json_decode(file_get_contents(CONFIG_PATH), true);
            usleep(FLOCK_SLEEP_INTERVAL);
        }
        return $chain;
    }

    public function execute(): void
    {
        parent::execute();
        if (!$this->isCommandAlreadyExecuted) {
            $chain = $this->getChain();
            $this->updateDataBase($chain);
        }
    }
}
