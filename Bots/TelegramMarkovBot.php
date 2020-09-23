<?php

namespace Bots;

use Commands\CommandListener;
use Misc\Input\IReader;
use Misc\MarkovChains;

class TelegramMarkovBot extends TelegramBot
{
    protected static $filterRegEx = [
        "urlFilter" => "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
        "punctiationFilter" => "/(?<!\w)[.,!]/",
        "newlineFilter" => "/\r|\n/",
    ];

    /**
     * @var MarkovChains
     */
    protected $markov;

    /**
     * TelegramMarkovBot constructor.
     * @param CommandListener $listener
     * @param IReader $reader
     * @throws \Exception
     */
    public function __construct($listener, $reader)
    {
        parent::__construct($listener, $reader);
        $this->markov = new MarkovChains(CONFIG_PATH);
    }

    public function execute()
    {
        parent::execute();
        if (!$this->isCommandAlreadyExecuted) {
            $preparedText = strtolower($this->rawText);
            foreach (self::$filterRegEx as $pattern) {
                $preparedText = preg_replace($pattern, " ", $preparedText);
            }
            $this->markov->loadChainFromFile();
            $this->markov->train($preparedText);
            $this->markov->saveChain();
        }
    }
}
