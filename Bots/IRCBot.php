<?php

declare(strict_types = 1);

namespace Bots;

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

    public function __construct(string $server, string $port, string $username, string $password, array $channels)
    {
        parent::__construct($server, $port);
        $this->username = $username;
        $this->password = $password;
        $this->channels = $channels;
    }

    public function setIterTimeout(int $microSeconds)
    {
        $this->timeoutMicro = $microSeconds;
    }

    public function execute() : void
    {
        parent::execute();
        $this->login();
        $this->joinChannels();
        $this->do();
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

    protected function do()
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

    public abstract function processMessage(string $message) : bool;
    
}