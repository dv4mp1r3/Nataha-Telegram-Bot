<?php

declare(strict_types=1);

namespace Bots;

use Commands\CommandListener;
use Misc\Input\IReader;
use Misc\MarkovChains;

class TelegramMarkovBot extends TelegramBot
{
    protected static array $filterRegEx = [
        "urlFilter" => "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@",
        "punctiationFilter" => "/(?<!\w)[.,!]/",
        "newlineFilter" => "/\r|\n/",
    ];

    /**
     * @var MarkovChains
     */
    protected MarkovChains $markov;

    public function __construct(CommandListener $listener, IReader $reader)
    {
        parent::__construct($listener, $reader);
        $this->markov = new MarkovChains(CONFIG_PATH);
    }

    public function execute(): void
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
