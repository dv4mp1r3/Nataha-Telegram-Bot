<?php


namespace Bots;


use pbot\Bots\PbotException;

class TelegramSecurityExpertMockBot extends TelegramSecurityExpertBot
{
    public function getFilePath(string $id): string
    {
        return getenv('TELEGRAM_MOCK_URL');
    }

    public function downloadFile(string $filePath): string
    {
        $ch = $this->buildCurlGetTemplate($filePath);
        $image = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            throw new PbotException(curl_error($ch));
        }

        curl_close($ch);
        return $image;
    }

    public function sendPhoto(int $chatId, string $fileContent): array
    {
        $filePath = '/var/www/'.uniqid('img_').'.jpg';
        $res = file_put_contents($filePath, $fileContent);
        return [];
    }
}