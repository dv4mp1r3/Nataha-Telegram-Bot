<?php


namespace Bots;


use Bots\Events\TwitchBeforeSendEvent;
use Misc\MarkovChains;
use Misc\SecurityExpert;
use Panda\Yandex\SpeechKitSDK\Cloud;
use Panda\Yandex\SpeechKitSDK\Lang;
use Panda\Yandex\SpeechKitSDK\Ru;
use Panda\Yandex\SpeechKitSDK\Speech;

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
            while(true) {
                $genText = $this->m->generateText(15);
                $wordsCount = mb_substr_count($genText, " ");
                if ($wordsCount >= 3) {
                    break;
                }
            }
            $str = "PRIVMSG #".$this->channels[0].' :'.$genText."\r\n";
            /**
             * @var TwitchBeforeSendEvent $beforeStartEvent
             */
            $beforeStartEvent = $this->beforeSendEvent;
            $beforeStartEvent->setEventData($genText);
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

    protected function isReply($lowerMessage) : bool
    {
        return preg_match('/:@securityexpert/i', $lowerMessage);
    }
}