<?php

declare(strict_types=1);

use Misc\Logger;
use Misc\Application;
use Bots\TwitchSecurityExpertBot;

require_once './config.php';
require_once './vendor/autoload.php';

global  $twitchData;
global  $yaCloudData;

$logger = new Logger();
(new Application(
    (new TwitchSecurityExpertBot(
        TWITCH_DEFAULT_SERVER,
        TWITCH_DEFAULT_PORT,
        $twitchData['username'],
        $twitchData['password'],
        ['dv4mp1r3'],
        CONFIG_PATH))
        /*->setEvent(\Bots\SocketBot::BEFORE_SEND_EVENT,
            new \Bots\Events\TwitchBeforeSendEvent(
                "http://node:3000/audio",
                $yaCloudData['token'],
                $yaCloudData['folder'])
        )*/,
    $logger,
    defined('IS_DEBUG') && IS_DEBUG
))->run();
