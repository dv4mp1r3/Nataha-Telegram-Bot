<?php

declare(strict_types = 1);

namespace Bots;

class SocketBot implements IBot
{

    /**
     * @var string
     */
    protected $server;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var resource
     */
    private $s;

    public function __construct(string $server, string $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public function execute() : void
    {
        $this->openConnection();
        
    }

    public function __desctruct()
    {
        $this->closeConnection();
    }

    protected function openConnection()
    {
        $this->s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (socket_connect($this->s, $this->server, intval($this->port)) === false) {
            throw new \Exception("socket_connect() failed: "
                . socket_strerror(socket_last_error($this->s)));
        }
    }

    protected function closeConnection()
    {
        if ($this->s)
        {
            \socket_close($this->s);
        }
    }

    protected function sendString(string $string)
    {
        $size = strlen($string);
        $i = \socket_write($this->s, $string, $size);
        $tmp = socket_last_error($this->s);
        $ts = socket_strerror($tmp);
    }

    protected function receiveString(int $len, int $type) : string
    {
        $buffer = '';
        $i = socket_recv($this->s, $buffer, $len, $type);
        $tmp = socket_last_error($this->s);
        $ts = socket_strerror($tmp);
        if ($i === 0 || !$i)
        {
            return '';
        }
        return $buffer;    
    }

    protected function getConnectionLastErrorCode() : int
    {
        return socket_last_error($this->s);
    }

    protected function getConnectionLastError() : string
    {
        return socket_strerror($this->getConnectionLastErrorCode());
    }
}