<?php

declare(strict_types=1);

namespace tests\Events;

use Bots\Events\TwitchBeforeSendEvent as tw;

class TwitchBeforeSendEvent extends tw
{
    private string $fileName;

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function runSaveMessage() : void
    {
        $this->fileName = $this->saveMessageAsVoice($this->eventData);
    }

    public function runPlayVoice() : void
    {
        $this->playVoice($this->fileName);
    }

    public function run()
    {
        $this->runSaveMessage();
        $this->runPlayVoice();
    }
}