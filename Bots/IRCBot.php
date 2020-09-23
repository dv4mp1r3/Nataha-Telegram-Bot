<?php

namespace Bots;

/**
 * Базовый класс для реализации irc-ботов
 * Необходима поддержка сокетов для работы
 * Для реализации нужно описать метод processMessage
 * @see TwitchSecurityExpertBot
 * @package Bots
 */
abstract class IRCBot extends SocketBot{

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var array
     */
    protected $channels;

    protected $timeoutMicro = 10000;

    /**
     * IRCBot constructor.
     * @param string $server
     * @param string $port
     * @param string $username
     * @param string $password
     * @param array $channels
     */
    public function __construct($server, $port, $username, $password, $channels)
    {
        parent::__construct($server, $port);
        $this->username = $username;
        $this->password = $password;
        $this->channels = $channels;
    }

    /**
     * @param int $microSeconds
     */
    public function setIterTimeout($microSeconds)
    {
        $this->timeoutMicro = $microSeconds;
    }

    public function execute()
    {
        parent::execute();
        $this->login();
        $this->joinChannels();
        $this->_do();
    }

    protected function login()
    {
        $this->sendString("PASS {$this->password}\n");
        $this->sendString("NICK {$this->username}\n");
    }

    protected function joinChannels()
    {
        foreach($this->channels as $channel)
        {
            $this->sendString("JOIN #$channel\n");
        }
    }

    protected function _do()
    {
        $buffer	= '';
        while(true)
        {
            $out = $this->receiveString(2048, MSG_DONTWAIT);
            if (mb_strlen($out) > 0) {
                echo "$out\n";
                $buffer .= $out;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $needToBreak = $this->processMessage(trim(substr($buffer, 0, $pos)));
                    if ($needToBreak)
                    {
                        break;
                    }
                    $buffer = substr($buffer, $pos+1);
                }
            }
            usleep($this->timeoutMicro);
        }
    }

    /**
     * @param string $message
     * @return bool
     */
    public abstract function processMessage($message);
    
}