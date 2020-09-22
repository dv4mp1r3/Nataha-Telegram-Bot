<?php

declare(strict_types = 1);

namespace Bots;

use Bots\Events\IEvent;

class SocketBot implements IBot
{

    const BEFORE_SEND_EVENT = 'beforeSend';
    /**
     * @var string
     */
    protected string $server;

    /**
     * @var string
     */
    protected string $port;

    /**
     * @var resource
     */
    private $s;

    /**
     * @var IEvent
     */
    protected IEvent $beforeSendEvent;

    public function setEvent(string $eventType, IEvent $event) : SocketBot
    {
        switch ($eventType)
        {
            case self::BEFORE_SEND_EVENT:
                $this->beforeSendEvent = $event;
            default:
                return $this;
        }
        return $this;
    }

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

    /**
     * @throws \Exception
     */
    protected function openConnection() : void
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

    /**
     * @param string $function
     * @param int $returnValue
     */
    protected function debugPrintSocketError(string $function, int $returnValue)
    {
        if (defined('IS_DEBUG') && IS_DEBUG)
        {
            $tmp = socket_last_error($this->s);
            $ts = socket_strerror($tmp);
            if($tmp === SOCKET_EAGAIN)
            {
                return;
            }
            echo "{$function}: {$ts} ($tmp)\n";
            if ($returnValue)
            {
                echo "return value $returnValue\n";
            }
        }
    }

    protected function sendString(string $string, bool $startEvents = false)
    {
        if ($startEvents && $this->beforeSendEvent instanceof IEvent)
        {
            $this->beforeSendEvent->run();
        }
        $size = strlen($string);
        $i = \socket_write($this->s, $string, $size);
        $this->debugPrintSocketError(__FUNCTION__, $i);
    }

    protected function receiveString(int $len, int $type) : string
    {
        $buffer = '';
        $i = socket_recv($this->s, $buffer, $len, $type);
        $this->debugPrintSocketError(__FUNCTION__, $i);
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