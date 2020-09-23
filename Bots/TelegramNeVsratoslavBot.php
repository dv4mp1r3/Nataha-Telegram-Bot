<?php

namespace Bots;

use Misc\MemeTextFromPDO;
use Misc\NeVsratoslav;
use Misc\SecurityExpert;

class TelegramNeVsratoslavBot extends TelegramSecurityExpertBot
{
    const ERROR_SEND_PHOTO = 'Не удалось загрузить фото';

    private $fontPath;

    private $mQuery;

    private $pdo;

    /**
     * @param string $fontPath
     * @return $this
     */
    public function setFontPath($fontPath)
    {
        $this->fontPath = $fontPath;
        return $this;
    }

    /**
     * @param string $mQuery
     * @return $this
     */
    public function setMemeTextQuery($mQuery)
    {
        $this->mQuery = $mQuery;
        return $this;
    }

    /**
     * @param \PDO $pdo
     * @return $this
     */
    public function setMemTextPdo($pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * @param array $message
     * @return bool
     */
    public function isImageReply($message)
    {
        return $this->isReply($message, '') && $this->replMessageIsImage($message);
    }

    /**
     * @param array $message
     * @return bool
     */
    protected function replMessageIsImage($message)
    {
        return array_key_exists('message', $message) &&
            array_key_exists('reply_to_message', $message['message']) &&
            array_key_exists('photo', $message['message']['reply_to_message']) &&
            is_array($message['message']['reply_to_message']['photo']) &&
            count($message['message']['reply_to_message']['photo']) > 0;
    }

    public function execute()
    {
        if ($this->isCommandAlreadyExecuted) {
            return;
        }
        $nvsrt = new NeVsratoslav();

        if ($this->chatId != ID_CREATOR && $this->chatId != ID_CHAT) {
            $this->sendMessage($this->chatId, SecurityExpert::MESSAGE_GET_OFF);
            return;
        }

        $lowerRawText = mb_strtolower($this->rawText);
        if ($this->isImageReply($this->decodedInput) && $nvsrt->isReply($lowerRawText)) {
            $filePath = $this->getFilePath($this->decodedInput['message']['reply_to_message']['photo'][0]['file_id']);
            $image = $this->downloadFile($filePath);
            $image = $nvsrt->addTextToImage(
                $image,
                MemeTextFromPDO::getRandomString($this->pdo, $this->mQuery),
                $this->fontPath
            );
            try {
                $this->sendPhoto($this->chatId, $image);
                return;
            } catch (\Exception $e) {
                $messageText = self::ERROR_SEND_PHOTO;
                if (defined('IS_DEBUG') && IS_DEBUG) {
                    $messageText .= "\r\n" . $e->getMessage();
                }
                $this->sendMessage($this->chatId, $messageText);
            }
            return;
        }
        parent::execute();
    }

}