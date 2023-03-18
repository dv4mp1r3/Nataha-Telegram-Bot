<?php

/**
 * Своя логика обработки сгенерированного текста
 * @param string $text результат выполнения generateText
 * @return string
 */
function customTextProcessing(string $text) : string
{
    return $text;
}

define('IDENT', getenv('IDENT'));
define('IS_DEBUG', getenv('IS_DEBUG'));
define('MAX_MESSAGE_LENGTH', getenv('MAX_MESSAGE_LENGTH'));
define('FLOCK_SLEEP_INTERVAL', getenv('FLOCK_SLEEP_INTERVAL'));
define('MAX_DB_READ_TRY', getenv('MAX_DB_READ_TRY'));
define('ID_CREATOR', getenv('ID_CREATOR'));
define('ID_CHAT', getenv('ID_CHAT'));
define('MAX_WORDS_COUNT', getenv('MAX_WORDS_COUNT'));
define('CONFIG_PATH', __DIR__.'/data.json');

define('TWITCH_DEFAULT_SERVER', getenv('TWITCH_DEFAULT_SERVER'));
define('TWITCH_DEFAULT_PORT', getenv('TWITCH_DEFAULT_PORT'));

define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN'));
define('PDO_MEME_DSN', getenv('PDO_MEME_DSN'));
define('PDO_MEME_QUERY', "SELECT text 
FROM nevsratoslav 
WHERE id >= (abs(random()) % (SELECT max(id) FROM nevsratoslav)) + 1
LIMIT 1");
