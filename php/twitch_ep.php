<?php

declare(strict_types=1);

use Misc\Logger;
use Misc\Application;
use Bots\TwitchSecurityExpertBot;

require_once './config.php';
require_once './vendor/autoload.php';

$logger = new Logger();
(new Application(
    (new TwitchSecurityExpertBot(
        TWITCH_DEFAULT_SERVER,
        TWITCH_DEFAULT_PORT,
        getenv('TWITCH_USERNAME'),
        getenv('TWITCH_PASSWORD'),
        [getenv('TWITCH_CHANNEL_NAME')],
        CONFIG_PATH))
        ->setEvent(\Bots\SocketBot::BEFORE_SEND_EVENT,
            new \Bots\Events\TwitchBeforeSendEvent(
                "http://node:3000/audio",
                getenv('YA_CLOUD_TOKEN'),
                getenv('YA_CLOUD_FOLDER'))
        ),
    $logger,
    defined('IS_DEBUG') && IS_DEBUG
))->run();
