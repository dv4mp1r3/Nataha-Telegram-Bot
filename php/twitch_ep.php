<?php

declare(strict_types=1);

use pbot\Misc\Logger;
use pbot\Misc\Application;
use pbot\Bots\SocketBot;
use Bots\Events\TwitchBeforeSendEvent;
use Bots\TwitchSecurityExpertBot;

require_once './config.php';
require_once './vendor/autoload.php';

$logger = new Logger();
$bot = new TwitchSecurityExpertBot(
    TWITCH_DEFAULT_SERVER,
    TWITCH_DEFAULT_PORT,
    getenv('TWITCH_USERNAME'),
    getenv('TWITCH_PASSWORD'),
    [getenv('TWITCH_CHANNEL_NAME')],
    CONFIG_PATH
);
if (intval(getenv('USE_DISCORD')) === 1) {
    $nodeHost = getenv('DISCORD_SERVICE_HOSTNAME');
    $bot->setEvent(
    SocketBot::BEFORE_SEND_EVENT,
    new TwitchBeforeSendEvent(
        "http://$nodeHost:3000/audio",
        getenv('YA_CLOUD_TOKEN'),
        getenv('YA_CLOUD_FOLDER'))
    );
}
(new Application($bot, $logger, defined('IS_DEBUG') && IS_DEBUG))->run();
