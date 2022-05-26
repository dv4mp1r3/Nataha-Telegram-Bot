<?php

declare(strict_types=1);

namespace Bots;

use pbot\Commands\CommandListener;
use pbot\Misc\Input\IReader;
use pbot\Bots\TelegramBot;
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

    /**
     * TelegramMarkovBot constructor.
     * @param IReader $reader
     * @param CommandListener|null $listener
     * @throws \Exception
     */
    public function __construct(IReader $reader, CommandListener $listener = null)
    {
        parent::__construct($reader, $listener);
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
