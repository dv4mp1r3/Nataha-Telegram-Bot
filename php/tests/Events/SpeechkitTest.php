<?php

declare(strict_types=1);

namespace tests\Events;

use PHPUnit\Framework\TestCase;

class SpeechkitTest extends TestCase
{
    public function testTts(): void
    {
        $event = new TwitchBeforeSendEvent('', getenv('YA_CLOUD_TOKEN'), getenv('YA_CLOUD_FOLDER'));
        $event->setEventData('test string');
        $event->runSaveMessage();
        $filePath = '/tmp/'.$event->getFileName();
        $this->assertTrue(file_exists($filePath), 'Voice file exists');
        unlink($filePath);
    }
}