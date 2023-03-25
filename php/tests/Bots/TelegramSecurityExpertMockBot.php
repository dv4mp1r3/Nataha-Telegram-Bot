<?php

declare(strict_types=1);

namespace tests\Bots;

use Bots\TelegramSecurityExpertBot;

class TelegramSecurityExpertMockBot extends TelegramSecurityExpertBot
{
    private array $lastResult = [];
    public function getFilePath(string $id): string
    {
        return __DIR__.'/../input/t1.jpg';
    }

    public function downloadFile(string $filePath): string
    {
        return file_get_contents($this->getFilePath(''));
    }

    public function getLastResult(): array
    {
        return $this->lastResult;
    }

    public function sendPhoto(int $chatId, string $fileContent): array
    {
        $this->lastResult = [];
        $filePath = '/var/www/'.uniqid('img_').'.jpg';
        $res = file_put_contents($filePath, $fileContent);
        $this->lastResult = [
            'path' => $filePath,
            'content' => $fileContent,
            'res' => $res,
        ];
        return $this->lastResult;
    }
}