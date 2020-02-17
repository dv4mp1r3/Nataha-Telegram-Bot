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
        if (strpos($message, 'PING') !== false)
        {
            $this->sendString("PONG :{$this->server}\r\n");
            return false;
        }
        $lowerMessage = mb_strtolower($message);
        if ($this->sExpert->isReply($lowerMessage) || $this->isReply($lowerMessage))
        {
            $str = "PRIVMSG #".$this->channels[0].' :'.$this->m->generateText(10)."\r\n";
            $this->sendString($str);
        }
        if($this->sExpert->isHaha($lowerMessage))
        {
            $lulStr = str_repeat("LUL ", rand(1, 5));
            $str = "PRIVMSG #".$this->channels[0]." :$lulStr\r\n";
            $this->sendString($str);
        }
        return false;
    }

    protected function isReply($lowerMessage) : bool
    {
        return preg_match('/:@securityexpert/i', $lowerMessage);
    }
}