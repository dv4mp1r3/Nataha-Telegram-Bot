<?php

declare(strict_types=1);

namespace Bots;

use Commands\CommandListener;
use Misc\Input\IReader;

/**
 * Базовый бот для обработки входящей инфы от телеграма
 * todo: вынести отправку пакетов через curl в отдельный класс
 */
class TelegramBot extends AbstractBaseBot
{
    const MESSAGE_ERROR_TEMPLATE = "SOMETHING WRONG\n:";
    const MESSAGE_TYPE_TEXT = 'sendMessage';
    const MESSAGE_TYPE_STICKER = 'sendSticker';

    const FUNCTION_GETFILE = 'getFile';

    const API_URL = 'https://api.telegram.org';

    protected array $decodedInput = [];

    protected string $rawText = '';

    protected int $chatId;

    /**
     * Устанавливается в true после первой выполненной команды
     * @see execute
     * @var bool
     */
    protected bool $isCommandAlreadyExecuted = false;

    public function __construct(CommandListener $listener = null, IReader $reader)
    {
        $keyMessage = 'message';
        $this->decodedInput = json_decode($reader->readAll(), true);
        if (!is_array($this->decodedInput) || !isset($this->decodedInput[$keyMessage]['chat']['id'])) {
            throw new \Exception('Bad data format');
        }

        $this->rawText = $this->parseRawText($this->decodedInput[$keyMessage]);
        if (strlen($this->rawText) > MAX_MESSAGE_LENGTH) {
            throw new \Exception('Data length is bigger then 300');
        }

        $this->chatId = $this->decodedInput[$keyMessage]['chat']['id'];
        parent::__construct($listener);
    }

    protected function parseRawText($message): string
    {
        $keys = ['text', 'caption'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $message)) {
                return $message[$key];
            }
        }
        return '';
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
     * @param int $chatId
     * @param string $text
     * @param string $method
     * @throws \InvalidArgumentException
     */
    protected function sendMessage(int $chatId, string $text, string $method = 'sendMessage')
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

    protected function checkTelegramOutput($stringOutput): array
    {
        $data = json_decode($stringOutput, true);
        if (json_last_error() > 0) {
            throw new \Exception(json_last_error_msg());
        }
        if ($data['ok'] !== true) {
            throw new \Exception("error {$data['error_code']}: {$data['description']}");
        }
        return $data;
    }

    /**
     * @param string $url
     * @return false|resource
     * @throws \Exception
     */
    protected function buildCurlGetTemplate(string $url)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \Exception("Error on curl_init");
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        return $ch;
    }

    protected function getFilePath(string $id): string
    {
        $url = self::buildFunctionUrl('getFile', ['file_id' => $id]);
        $ch = $this->buildCurlGetTemplate($url);
        $fileData = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);
        $fileData = $this->checkTelegramOutput($fileData);
        return $fileData['result']['file_path'];
    }

    protected function sendPhoto(int $chatId, string $fileContent): array
    {
        $boundary = uniqid();
        $eol = "\r\n";
        $name = 'photo';
        $delimiter = '-------------' . $boundary;
        $url = self::buildFunctionUrl(__FUNCTION__, ['chat_id' => $chatId]);
        $data = "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
            . 'Content-Type: image/jpeg' . $eol;
        $data .= $eol;
        $data .= $fileContent . $eol;
        $data .= "--" . $delimiter . "--" . $eol;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            [
                "Content-Type: multipart/form-data; boundary=$delimiter",
                "Content-Length: " . strlen($data)
            ]
        );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);
        $fileData = $this->checkTelegramOutput($output);
        return $fileData;
    }

    protected function downloadFile($filePath): string
    {
        if (!defined('TELEGRAM_BOT_TOKEN')) {
            throw new \Exception('constant TELEGRAM_BOT_TOKEN is not defined');
        }

        $apiUrl = self::API_URL . '/file/bot' . TELEGRAM_BOT_TOKEN . '/' . $filePath;
        $ch = $this->buildCurlGetTemplate($apiUrl);
        $image = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);
        return $image;
    }

    /**
     * @param string $function имя функции апи
     * @param array $params массив параметров (ключ-значение)
     * @return string
     * @throws \Exception
     */
    protected static function buildFunctionUrl(string $function, array $params = []): string
    {
        if (!defined('TELEGRAM_BOT_TOKEN')) {
            throw new \Exception('constant TELEGRAM_BOT_TOKEN is not defined');
        }

        $apiUrl = self::API_URL . '/bot' . TELEGRAM_BOT_TOKEN . '/' . $function;
        if (count($params) > 0) {
            $apiUrl .= '?' . http_build_query($params);
        }
        return $apiUrl;
    }

    /**
     *
     * @param array $message
     * @param string $ident
     * @return boolean
     */
    public function isReply(array $message, string $ident = IDENT): bool
    {
        $keyReplyTo = 'reply_to_message';
        $keyMessage = 'message';
        if (empty($message[$keyMessage][$keyReplyTo])) {
            return false;
        }

        if (empty($message[$keyMessage][$keyReplyTo]['from'])) {
            return false;
        }

        if (mb_strlen($ident) > 0) {
            return $message[$keyMessage][$keyReplyTo]['from']['username'] === $ident;
        }
        return true;
    }
}
