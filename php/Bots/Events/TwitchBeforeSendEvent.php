<?php

declare(strict_types=1);

namespace Bots\Events;

use pbot\Bots\Events\IEvent;
use Misc\YaCloud;
use Panda\Yandex\SpeechKitSdk\Emotion;
use Panda\Yandex\SpeechKitSdk\Lang;
use Panda\Yandex\SpeechKitSdk\Voice\Ru;
use Panda\Yandex\SpeechKitSdk\Synthesize;
use Bots\Exception;

class TwitchBeforeSendEvent implements IEvent
{
    /**
     * @var YaCloud
     */
    protected YaCloud $cloud;

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
            $this->cloud = new YaCloud($token, $folder);
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
     * @throws \Exception
     */
    protected function saveMessageAsVoice(string $str) : string
    {
        $speech = new Synthesize($str);
        $speech->setVoice(Ru::OMAZH)
            ->setEmotion(Emotion::EVIL)
            ->setLang(Lang::RU_RU);
        $media = $this->cloud->request($speech);
        $maybeError = json_decode($media, true);
        if (json_last_error() === JSON_ERROR_NONE
            && array_key_exists('error_code', $maybeError)) {
            throw new Exception("Speechkit error: {$maybeError['error_code']}");
        }
        $fileName = md5((string)time()).'.ogg';
        $filePath = "/tmp/$fileName";
        if (!file_put_contents($filePath, $media)) {
            throw new Exception("Can't write $filePath");
        }
        return $fileName;
    }

    protected function playVoice(string $fileName)
    {
        $filePath = "/tmp/$fileName";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->nodeUrl}/{$fileName}");
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'voice' => new \CurlFile($filePath)
        ]);
        curl_exec($ch);
        unlink($fileName);
        if (curl_errno($ch) !== CURLE_OK) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception(__FUNCTION__ . " error: $error");
        }
        curl_close($ch);
    }

    public function run()
    {
        $fileName = $this->saveMessageAsVoice($this->eventData);
        $this->playVoice($fileName);
    }
}