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
     * @var string
     */
    protected string $nodeUrl;

    /**
     * @var string
     */
    protected string $eventData;

    /**
     * TwitchBeforeSendEvent constructor.
     * @param string $nodeUrl
     * @param string $token
     * @param string $folder
     */
    public function __construct(string $nodeUrl, string $token, string $folder)
    {
        try
        {
            $this->nodeUrl = $nodeUrl;
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
     * @return string
     */
    protected function saveMessageAsVoice(string $str) : string
    {
        $speech = new Speech($str);
        $speech->setVoice(Ru::OMAZH)
            ->setEmotion(Emotion::EVIL)
            ->setLang(Lang::RU);
        $media = $this->cloud->request($speech);
        $maybeError = json_decode($media, true);
        if (json_last_error() === JSON_ERROR_NONE
            && array_key_exists('error_code', $maybeError)) {
            throw new \Exception("Speechkit error: {$maybeError['error_code']}");
        }
        $fileName = md5((string)time()).'.ogg';
        file_put_contents("{$_ENV['PWD']}/audio/$fileName", $media);
        return $fileName;
    }

    protected function playVoice(string $fileName)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->nodeUrl}/{$fileName}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
    }

    public function run()
    {
        $fileName = $this->saveMessageAsVoice($this->eventData);
        $this->playVoice($fileName);
    }
}