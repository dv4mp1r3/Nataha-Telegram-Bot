<?php

namespace Bots;

/**
 * Базовый бот для обработки входящей инфы от телеграма
 */
class TelegramBot extends BaseBot
{
    const MESSAGE_TYPE_TEXT = 'sendMessage';
    const MESSAGE_TYPE_STICKER = 'sendSticker';
    
    protected $decodedInput = '';
    
    protected $rawText = '';
    
    protected $chatId;
    
    public function __construct()
    {
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
        
        if (!is_array($sJ) || !isset($sJ['message']['chat']['id'])) {
            throw new \Exception('Bad data format');
        }
        
        $this->decodedInput = $sJ;
        
        $this->rawText = $this->decodedInput['message']['text'];
        if (strlen($this->rawText) > MAX_MESSAGE_LENGTH) {
            throw new \Exception('Data length is bigger then 300');
        }
        
        $this->chatId = $this->decodedInput['message']['chat']['id'];
    }
    
    public function execute()
    {
        
    }
    
    /**
     * 
     * @param string $chatId
     * @param string $text
     * @param string $method
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
        if (empty($message['message']['reply_to_message']))
        {
            return false;
        }

        if (empty($message['message']['reply_to_message']['from']))
        {
            return false;
        }

        return $message['message']['reply_to_message']['from']['username'] === IDENT;

    }
}
