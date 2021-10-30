<?php
declare(strict_types=1);

namespace Misc;


use Panda\Yandex\SpeechKitSdk\Cloud;

class YaCloud extends Cloud
{
    protected function send(string $url, ?string $data, array $headers): string
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($headers !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        if (isset($file)) {
            fclose($file);
        }

        if ($response === false) {
            throw new Exception\ClientException(curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}