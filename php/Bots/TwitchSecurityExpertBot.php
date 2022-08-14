<?php

declare(strict_types=1);

namespace Bots;

use pbot\Bots\Events\IEvent;
use pbot\Bots\Events\TwitchBeforeSendEvent;
use pbot\Bots\IRCBot;
use Misc\MarkovChains;
use Misc\SecurityExpert;

class TwitchSecurityExpertBot extends IRCBot
{
    /**
     * @var SecurityExpert
     */
    protected SecurityExpert $sExpert;

    /**
     * @var MarkovChains
     */
    protected MarkovChains $m;

    /**
     * TwitchSecurityExpertBot constructor.
     * @param string $server
     * @param string $port
     * @param string $username
     * @param string $password
     * @param array $channels
     * @param string $markovDatabaseFile
     * @throws \Exception
     */
    public function __construct(string $server, string $port, string $username, string $password, array $channels, string $markovDatabaseFile)
    {
        $this->sExpert = new SecurityExpert();
        $this->m = new MarkovChains($markovDatabaseFile);
        $this->m->loadChainFromFile();
        parent::__construct($server, $port, $username, $password, $channels);
    }

    /**
     * Обработка нового сообщения
     * @param string $message
     * @return bool
     * @throws \Exception
     */
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
            while(true) {
                $genText = $this->m->generateText(15);
                $wordsCount = mb_substr_count($genText, " ");
                if ($wordsCount >= 2) {
                    break;
                }
            }
            $str = "PRIVMSG #".$this->channels[0].' :'.$genText."\r\n";
            /**
             * @var TwitchBeforeSendEvent $event
             */
            foreach ($this->beforeSendEvents as $event) {
                if ($event instanceof IEvent) {
                    $event->setEventData($genText);
                }
            }

            $this->sendString($str, true);
        }
        if($this->sExpert->isHaha($lowerMessage))
        {
            $lulStr = str_repeat("LUL ", rand(1, 5));
            $str = "PRIVMSG #".$this->channels[0]." :$lulStr\r\n";
            $this->sendString($str);
        }
        return false;
    }

    /**
     * Поиск в тексте сообщения упоминания бота
     * @param $lowerMessage
     * @return bool
     */
    protected function isReply($lowerMessage) : bool
    {
        return preg_match('/:@securityexpert/i', $lowerMessage) > 0;
    }
}