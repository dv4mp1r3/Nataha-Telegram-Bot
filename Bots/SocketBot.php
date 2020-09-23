<?php

namespace Bots;

use Bots\Events\IEvent;

class SocketBot implements IBot
{

    const BEFORE_SEND_EVENT = 'beforeSend';
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

    /**
     * @var IEvent
     */
    protected $beforeSendEvent;

    /**
     * @param string $eventType
     * @param IEvent $event
     * @return SocketBot
     */
    public function setEvent($eventType, $event)
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

    /**
     * SocketBot constructor.
     * @param string $server
     * @param string $port
     */
    public function __construct($server, $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public function execute()
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

    /**
     * @param string $function
     * @param int $returnValue
     */
    protected function debugPrintSocketError($function, $returnValue)
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

    /**
     * @param string $string
     * @param bool $startEvents
     */
    protected function sendString($string, $startEvents = false)
    {
        if ($startEvents && $this->beforeSendEvent instanceof IEvent)
        {
            $this->beforeSendEvent->run();
        }
        $size = strlen($string);
        $i = \socket_write($this->s, $string, $size);
        $this->debugPrintSocketError(__FUNCTION__, $i);
    }

    /**
     * @param int $len
     * @param int $type
     * @return string
     */
    protected function receiveString($len, $type)
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

    protected function getConnectionLastErrorCode()
    {
        return socket_last_error($this->s);
    }

    protected function getConnectionLastError()
    {
        return socket_strerror($this->getConnectionLastErrorCode());
    }
}