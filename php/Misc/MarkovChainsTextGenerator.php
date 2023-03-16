<?php


namespace Misc;


class MarkovChainsTextGenerator extends MarkovChains implements TextGenerator
{

    private int $wordsCount;

    public function __construct(string $filePath, int $maxWordsCount = 5)
    {
        parent::__construct($filePath);
        $this->loadChainFromFile();
        $this->wordsCount = $maxWordsCount;
    }

    public function genString(): string
    {
        return $this->generateText($this->wordsCount);
    }
}