<?php

namespace Bots;

use Commands\ICommand;

/**
 * Базовый бот для обработки входящей инфы от телеграма
 */
class TelegramBot implements IBot
{
    const MESSAGE_ERROR_TEMPLATE = "SOMETHING WRONG\n:";
    const MESSAGE_TYPE_TEXT = 'sendMessage';
    const MESSAGE_TYPE_STICKER = 'sendSticker';
    
    protected $decodedInput = '';
    
    protected $rawText = '';
    
    protected $chatId;

    /**
     * key-value массив для обработки команд
     * key - команда, value - класс для обработки
     * @see execute
     * @var array
     */
    protected $registeredCommands = [];

    /**
     * Устанавливается в true после первой выполненной команды
     * @see execute
     * @var bool
     */
    protected $isCommandAlreadyExecuted = false;
    
    public function __construct()
    {
        $keyMessage = 'message';
        if (!defined('IS_DEBUG') || !IS_DEBUG)
        {
            $input = file_get_contents("php://input"); 
            $sJ = json_decode($input, true);
        }
        else
        {
            global $testData;
            $sJ = $testData;
        }
        
        if (!is_array($sJ) || !isset($sJ[$keyMessage]['chat']['id'])) {
            throw new \Exception('Bad data format');
        }
        
        $this->decodedInput = $sJ;
        
        $this->rawText = $this->decodedInput[$keyMessage]['text'];
        if (strlen($this->rawText) > MAX_MESSAGE_LENGTH) {
            throw new \Exception('Data length is bigger then 300');
        }
        
        $this->chatId = $this->decodedInput[$keyMessage]['chat']['id'];
    }

    /**
     * Добавление команды на обработку
     * @param string $command
     * @param string $commandClassName
     * @return bool
     */
    public function registerCommand($command, $commandClassName)
    {
        if (!class_exists($commandClassName) || !is_string($command))
        {
            return false;
        }

        $this->registeredCommands[$command] = $commandClassName;
        return true;
    }

    /**
     * В случае если боту передана команда - парсит аргументы
     * и возвращает их в виде массива
     * В качестве разделителя используется пробел
     * @param string $commandName
     * @return array
     * @throws \ErrorException
     */
    protected function parseCommandArgs($commandName)
    {
        if (!function_exists('mb_explode'))
        {
            throw new \ErrorException('Function mb_explode is not exists');
        }
        $argsString = mb_strcut($this->rawText, mb_strlen($commandName));
        return mb_explode(' ', $argsString);
    }

    /**
     * Попытка обработки зарегистрированных команд
     * @see registerCommand
     * @return mixed
     */
    public function execute()
    {
        try
        {
            foreach ($this->registeredCommands as $commandName => $className)
            {
                if (mb_stripos($this->rawText, $commandName) === 0)
                {
                    $command = new $className();
                    if (!($command instanceof ICommand))
                    {
                        throw new \LogicException("command $className is not instance of ICommand");
                    }
                    $payload = $this->parseCommandArgs($commandName);
                    $result = $command->run($payload, $this->decodedInput);
                    $this->isCommandAlreadyExecuted = true;
                    $this->sendMessage($this->chatId, $result);
                    return;
                }
            }
        }
        catch (\Exception $ex)
        {
            if (defined('IS_DEBUG') && IS_DEBUG && defined('ID_CREATOR'))
            {
                $this->sendMessage(ID_CREATOR,$this->buildErrorReport());
            }
        }
    }

    /**
     * Сборка сообщения об ошибке
     * @param \Exception|null $ex
     * @return string
     */
    protected function buildErrorReport($ex = null)
    {
        $tgMessage = json_encode($this->decodedInput);
        $stackTrace = '';
        if ($ex instanceof \Exception)
        {
            $stackTrace = "STACK TRACE:\n".$ex->getTraceAsString();
        }

        return self::MESSAGE_ERROR_TEMPLATE."$tgMessage\n.$stackTrace";
    }
    
    /**
     * 
     * @param string $chatId
     * @param string $text
     * @param string $method
     * @throws \InvalidArgumentException
     */
    protected function sendMessage($chatId, $text, $method = 'sendMessage')
    {
        header("Content-Type: application/json");
        $reply['method'] = $method;
        $reply['chat_id'] = $chatId;
        switch($method)
        {
            case TelegramBot::MESSAGE_TYPE_TEXT:
                $reply['text'] = $text;
                break;
            case TelegramBot::MESSAGE_TYPE_STICKER:
                $reply['sticker'] = $text;
                break;
            default:
                throw new \InvalidArgumentException("Invalid method value: $method");
        }
        echo json_encode($reply);
    }
    
    /**
     * 
     * @param array $message
     * @return boolean
     */
    public function isReply($message)
    {
        $keyReplyTo = 'reply_to_message';
        $keyMessage = 'message';
        if (empty($message[$keyMessage][$keyReplyTo]))
        {
            return false;
        }

        if (empty($message[$keyMessage][$keyReplyTo]['from']))
        {
            return false;
        }

        return $message[$keyMessage][$keyReplyTo]['from']['username'] === IDENT;

    }
}
