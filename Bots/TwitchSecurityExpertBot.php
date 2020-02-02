<?php


namespace Bots;


use Misc\MarkovChains;
use Misc\SecurityExpert;

class TwitchSecurityExpertBot extends IRCBot
{
    /**
     * @var SecurityExpert
     */
    protected $sExpert;

    /**
     * @var MarkovChains
     */
    protected $m;

    public function __construct(string $server, string $port, string $username, string $password, array $channels, string $markovDatabaseFile)
    {
        $this->sExpert = new SecurityExpert();
        $this->m = new MarkovChains($markovDatabaseFile);
        $this->m->loadChainFromFile();
        parent::__construct($server, $port, $username, $password, $channels);
    }

    public function processMessage(string $message): bool
    {
        if (strpos($message, 'PING'))
        {
            $this->sendString("PONG\r\n");
            return false;
        }

        if ($this->sExpert->isReply(mb_strtolower($message)))
        {
            $str = "PRIVMSG #".$this->channels[0].' :'.$this->m->generateText(10)."\r\n";
            $this->sendString($str);
        }
        return false;
    }
}