<?php

if (PHP_VERSION_ID < 70200) {
    die("PHP >= 7.0 required" . PHP_EOL);
}

declare(strict_types=1);

require_once './config.php';
require_once './vendor/autoload.php';

global $twitchData;
global $yaCloudData;

(new \Misc\Application(
    (new \Bots\TwitchSecurityExpertBot(
        TWITCH_DEFAULT_SERVER,
        TWITCH_DEFAULT_PORT,
        $twitchData['username'],
        $twitchData['password'],
        ['dv4mp1r3'],
        CONFIG_PATH))
        ->setEvent(\Bots\SocketBot::BEFORE_SEND_EVENT,
            new \Bots\Events\TwitchBeforeSendEvent(
                '/tmp/security.pid',
                $yaCloudData['token'],
                $yaCloudData['folder'])
        ),
    defined('IS_DEBUG') && IS_DEBUG
))->run();
