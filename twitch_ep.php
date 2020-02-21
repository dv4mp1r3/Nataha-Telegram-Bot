<?php

declare(strict_types=1);

require_once './config.php';
require_once './vendor/autoload.php';

global  $twitchData;

(new \Misc\Application(
    new \Bots\TwitchSecurityExpertBot(
        TWITCH_DEFAULT_SERVER,
        TWITCH_DEFAULT_PORT,
        $twitchData['username'],
        $twitchData['password'],
        ['dv4mp1r3'],
        CONFIG_PATH),
    defined('IS_DEBUG') && IS_DEBUG
))->run();
