<?php

declare(strict_types=1);

namespace Bots\Events;

use Panda\Yandex\SpeechKitSDK\Cloud;
use Panda\Yandex\SpeechKitSDK\Emotion;
use Panda\Yandex\SpeechKitSDK\Lang;
use Panda\Yandex\SpeechKitSDK\Ru;
use Panda\Yandex\SpeechKitSDK\Speech;

class TwitchBeforeSendEvent implements IEvent
{
    /**
     * @var Cloud
     */
    protected Cloud $cloud;

    /**
     * @var int
     */
    protected int $nodePid;

    /**
     * @var string
     */
    protected string $eventData;

    /**
     * TwitchBeforeSendEvent constructor.
     * @param string $pidFile
     * @param string $token
     * @param string $folder
     */
    public function __construct(string $pidFile, string $token, string $folder)
    {
        try
        {
            $this->nodePid = intval(file_get_contents($pidFile));
            $this->cloud = new Cloud($token, $folder);
        }
        catch (ClientException $e)
        {
            echo $e->getMessage();
            echo PHP_EOL;
        }
    }

    /**
     * @param string $data
     */
    public function setEventData(string $data)
    {
        $this->eventData = $data;
    }

    /**
     * @param string $str
     */
    protected function saveMessageAsVoice(string $str)
    {
        $speech = new Speech($str);
        $speech->setVoice(Ru::OMAZH)
            ->setEmotion(Emotion::EVIL)
            ->setLang(Lang::RU);
        $media = $this->cloud->request($speech);
        file_put_contents('t.ogg', $media);
    }

    protected function playVoice()
    {
        posix_kill($this->nodePid, SIGUSR2);
    }

    public function run()
    {
        $this->saveMessageAsVoice($this->eventData);
        $this->playVoice();
    }
}