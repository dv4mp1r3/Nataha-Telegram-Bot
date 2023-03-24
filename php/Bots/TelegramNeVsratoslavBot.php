<?php

declare(strict_types=1);

namespace Bots;

use Misc\NeVsratoslav;
use Misc\SecurityExpert;
use Misc\TextGenerator;
use pbot\Bots\IBot;
use pbot\Bots\ParentBot;

class TelegramNeVsratoslavBot implements IBot, ParentBot
{
    const PARENT_MAX_WORDS = 100;
    const ERROR_SEND_PHOTO = 'Не удалось загрузить фото';

    private string $fontPath;

    private TextGenerator $generator;

    private ?IBot $parent;

    public function setParent(IBot $pb): void
    {
        $this->parent = $pb;
    }

    public function getParent(): TelegramSecurityExpertBot
    {
        return $this->parent;
    }

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
        return $this->getParent()->isReply($message, '') && $this->replMessageIsImage($message);
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
        $chatId = $this->getParent()->getChatId();
        if ($chatId != ID_CREATOR && $chatId != ID_CHAT) {
            $this->getParent()->sendMessage($chatId, SecurityExpert::MESSAGE_GET_OFF);
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
        $lowerRawText = mb_strtolower($this->getParent()->getRawText());
        $decodedInput = $this->getParent()->getDecodedInput();
        if ($this->isImageReply($decodedInput) && $nvsrt->isReply($lowerRawText)) {
            $lastPhotoIndex = count($decodedInput['message']['reply_to_message']['photo']) - 1;
            $lastPhoto = $decodedInput['message']['reply_to_message']['photo'][$lastPhotoIndex];
            $filePath = $this->getParent()->getFilePath($lastPhoto['file_id']);
            $image = $this->getParent()->downloadFile($filePath);
            $image = $nvsrt->addTextToImage(
                $image,
                $this->fontPath
            );
            try {
                $this->getParent()->sendPhoto($this->getParent()->getChatId(), $image);
                return;
            } catch (\Exception $e) {
                $messageText = self::ERROR_SEND_PHOTO;
                if (defined('IS_DEBUG') && IS_DEBUG) {
                    $messageText .= "\r\n" . $e->getMessage();
                }
                $this->getParent()->sendMessage($this->getParent()->getChatId(), $messageText);
            }
        } else {
            $this->getParent()->setMaxWordsCount(self::PARENT_MAX_WORDS);
            $this->getParent()->execute();
        }
    }

}