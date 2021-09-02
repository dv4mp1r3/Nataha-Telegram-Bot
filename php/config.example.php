<?php

/**
 * Своя логика обработки сгенерированного текста
 * @param string $text результат выполнения generateText
 * @return string
 */
function customTextProcessing($text)
{
    return $text;
}

define('IDENT', '');
define('IS_DEBUG', true);
define('MAX_MESSAGE_LENGTH', 300);
define('FLOCK_SLEEP_INTERVAL', 500000);
define('MAX_DB_READ_TRY', 15);
define('ID_CREATOR', 1);
define('ID_CHAT', 1);
define('CONFIG_PATH', __DIR__.'/data.json');

define('TWITTER_CONSUMER_KEY', '');
define('TWITTER_CONSUMER_SECRET', '');
define('TWITTER_ACCESS_TOKEN', '');
define('TWITTER_ACCESS_TOKEN_SECRET', '');

define('TWITCH_DEFAULT_SERVER', 'irc.chat.twitch.tv');
define('TWITCH_DEFAULT_PORT', '6667');

define('TELEGRAM_BOT_TOKEN', '');
define('PDO_MEME_DSN', "sqlite:meme.db");
define('PDO_MEME_QUERY', "SELECT text 
FROM nevsratoslav 
WHERE id >= (abs(random()) % (SELECT max(id) FROM nevsratoslav)) + 1
LIMIT 1");

$twitchData = [
    'username' => '',
    'password' => 'oauth:',
    'channels' => [''],
];

$discordData = [
    'token' => '',
];

$yaCloudData = [
    'token' => '',
    'folder' => '',
];


