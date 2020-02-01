<?php

declare(strict_types=1);

namespace Misc;

class MarkovChains
{
    const ARRAY_KEY_CHAIN = 'chain';

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $chain;

    protected $writeHumanReadable = false;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function useHumanReadableFormat(bool $use)
    {
        $this->writeHumanReadable = $use;
    }

    /**
     * генерация/обновление цепи
     * @param string $message
     * @param array $data
     * @return array
     */
    public function train(string $message): void
    {
        $array = explode(" ", $message);

        foreach ($array as $num => $val) {
            $val = base64_encode($val);
            if (!$val) {
                continue;
            }
            // if there is already a block for this word, keep it, otherwise create one
            $commit = (isset($this->chain[self::ARRAY_KEY_CHAIN][$val])
                ? $this->chain[self::ARRAY_KEY_CHAIN][$val]
                : array());
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
            $this->chain[self::ARRAY_KEY_CHAIN][$val] = $commit;
        }
    }

    /**
     * Генерация текста сообщения
     * @param integer $maxWords
     * @return string
     * @throws \Exception
     */
    public function generateText(int $maxWords): string
    {
        $text = [];
        $customTextProcessingFunctionName = 'customTextProcessing';
        if (empty($this->chain)) {
            throw new \Exception('Bad data format');
        }
        $out = array_rand($this->chain[self::ARRAY_KEY_CHAIN]); // initial word
        while (true) {
            $tmp = $this->chain[self::ARRAY_KEY_CHAIN][$out];
            if (is_null($tmp)) {
                break;
            }
            $out = $this->weighAndSelect($tmp);
            $text[] = base64_decode($out);
            if (count($text) > $maxWords) {
                break;
            }
        }

        $text = implode(" ", $text);

        if (function_exists($customTextProcessingFunctionName)) {
            return customTextProcessing($text);
        }

        return $text;
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

    public function saveChain(): bool
    {
        $putData = json_encode($this->chain);
        if ($putData !== "false") {
            $result = file_put_contents($this->filePath, $putData, LOCK_EX);
            if ($this->writeHumanReadable) {
                file_put_contents($this->chatId . ".json.txt", print_r($this->chain, true), LOCK_EX);
            }
            return is_int($result) && $result > 0;
        } else {
            throw new \Exception('$putData is false (file_put_contents error)');
        }
    }

    /**
     * Чтение и возврат цепи в виде ассоциированного массива
     * @return void
     * @throws \Exception
     */
    public function loadChainFromFile()
    {
        $tryCount = 0;
        $this->chain = json_decode(file_get_contents($this->filePath), true);
        while (!$this->chain) {
            if ($tryCount === MAX_DB_READ_TRY) {
                throw new \Exception("Can't get file content for " . $this->filePath);
            }
            $tryCount++;
            $this->chain = json_decode(file_get_contents($this->filePath), true);
            usleep(FLOCK_SLEEP_INTERVAL);
        }
    }

}