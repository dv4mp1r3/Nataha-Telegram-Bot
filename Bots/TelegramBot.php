<?php

declare(strict_types=1);

namespace Bots;

/**
 * Базовый бот для обработки входящей инфы от телеграма
 */
class TelegramBot extends AbstractBaseBot
{
    const MESSAGE_ERROR_TEMPLATE = "SOMETHING WRONG\n:";
    const MESSAGE_TYPE_TEXT = 'sendMessage';
    const MESSAGE_TYPE_STICKER = 'sendSticker';

    protected $decodedInput = '';

    protected $rawText = '';

    protected $chatId;

    /**
     * Устанавливается в true после первой выполненной команды
     * @see execute
     * @var bool
     */
    protected $isCommandAlreadyExecuted = false;

    public function __construct()
    {
        $keyMessage = 'message';
        if (!defined('IS_DEBUG') || !IS_DEBUG) {
            $input = file_get_contents("php://input");
            $sJ = json_decode($input, true);
        } else {
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
     * Попытка обработки зарегистрированных команд
     * @return mixed
     * @see registerCommand
     */
    public function execute(): void
    {
        try {
            if ($this->commandListener->isCommand($this->rawText)) {
                $this->isCommandAlreadyExecuted = true;
                $result = $this->commandListener->executeFoundCommand();
                $this->sendMessage($this->chatId, $result);
            }
        } catch (\Exception $ex) {
            if (defined('IS_DEBUG') && IS_DEBUG && defined('ID_CREATOR')) {
                $this->sendMessage(ID_CREATOR, $this->buildErrorReport());
            }
        }
    }

    /**
     * Сборка сообщения об ошибке
     * @param \Exception|null $ex
     * @return string
     */
    protected function buildErrorReport(\Exception $ex = null): string
    {
        $tgMessage = json_encode($this->decodedInput);
        $stackTrace = '';
        if ($ex instanceof \Exception) {
            $stackTrace = "STACK TRACE:\n" . $ex->getTraceAsString();
        }

        return self::MESSAGE_ERROR_TEMPLATE . "$tgMessage\n.$stackTrace";
    }

    /**
     *
     * @param string $chatId
     * @param string $text
     * @param string $method
     * @throws \InvalidArgumentException
     */
    protected function sendMessage(string $chatId, string $text, string $method = 'sendMessage')
    {
        header("Content-Type: application/json");
        $reply['method'] = $method;
        $reply['chat_id'] = $chatId;
        switch ($method) {
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
    public function isReply(array $message): bool
    {
        $keyReplyTo = 'reply_to_message';
        $keyMessage = 'message';
        if (empty($message[$keyMessage][$keyReplyTo])) {
            return false;
        }

        if (empty($message[$keyMessage][$keyReplyTo]['from'])) {
            return false;
        }

        return $message[$keyMessage][$keyReplyTo]['from']['username'] === IDENT;

    }
}
