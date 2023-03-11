<?php

declare(strict_types=1);

namespace Bots;

use Misc\NeVsratoslav;
use Misc\SecurityExpert;
use Misc\TextGenerator;

class TelegramNeVsratoslavBot extends TelegramSecurityExpertBot
{
    const ERROR_SEND_PHOTO = 'Не удалось загрузить фото';

    private string $fontPath;

    private TextGenerator $generator;

    /**
     * @param string $fontPath
     * @return $this
     */
    public function setFontPath(string $fontPath): self
    {
        $this->fontPath = $fontPath;
        return $this;
    }

    public function setTextGenerator(TextGenerator $tg): self
    {
        $this->generator = $tg;
        return $this;
    }

    /**
     * @param array $message
     * @return bool
     */
    public function isImageReply(array $message): bool
    {
        return $this->isReply($message, '') && $this->replMessageIsImage($message);
    }

    /**
     * @param array $message
     * @return bool
     */
    protected function replMessageIsImage(array $message): bool
    {
        return array_key_exists('message', $message) &&
            array_key_exists('reply_to_message', $message['message']) &&
            array_key_exists('photo', $message['message']['reply_to_message']) &&
            is_array($message['message']['reply_to_message']['photo']) &&
            count($message['message']['reply_to_message']['photo']) > 0;
    }

    private function isNoNeedToExecute(): bool {
        if ($this->isCommandAlreadyExecuted) {
            return true;
        }
        if ($this->chatId != ID_CREATOR && $this->chatId != ID_CHAT) {
            $this->sendMessage($this->chatId, SecurityExpert::MESSAGE_GET_OFF);
            return true;
        }
        return false;
    }

    public function execute(): void
    {
        if ($this->isNoNeedToExecute()) {
            return;
        }
        $nvsrt = new NeVsratoslav($this->generator);
        $lowerRawText = mb_strtolower($this->rawText);
        if ($this->isImageReply($this->decodedInput) && $nvsrt->isReply($lowerRawText)) {
            $filePath = $this->getFilePath($this->decodedInput['message']['reply_to_message']['photo'][0]['file_id']);
            $image = $this->downloadFile($filePath);
            $image = $nvsrt->addTextToImage(
                $image,
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
        } else {
            parent::execute();
        }
    }

}