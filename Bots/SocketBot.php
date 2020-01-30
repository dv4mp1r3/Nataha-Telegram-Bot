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

    public function __construct(string $server, string $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    /**
     * @var resource
     */
    private $s;

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
        if (socket_connect($this->s, $this->server, $this->port) === false) {
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
        \socket_write($this->s, $string, mb_strlen($string));
    }

    protected function receiveString(int $len, int $type) : string
    {
        $buffer = '';
        socket_recv($this->s, $buffer, $len, $type);
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