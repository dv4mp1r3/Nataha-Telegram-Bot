<?php

use Bots\TelegramSecurityExpertBot;
use Commands\CommandListener;
use Commands\HashIdCommand;
use Misc\Application;
use Misc\Logger;
use Misc\Input\FileReader;
use Misc\Input\PhpInputReader;

require_once './config.php';
require_once './vendor/autoload.php';

$reader = defined('IS_DEBUG') && IS_DEBUG
    ? (new FileReader(__DIR__ . '/input/text_chat.json'))
    : (new PhpInputReader());
$logger = new Logger();
try {
    //$db = new \PDO(PDO_MEME_DSN);
    //$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    (new Application(
        (new TelegramSecurityExpertBot(
            (new CommandListener())
                ->addCommand('/hashid', new HashIdCommand()),
            $reader
        )),
        defined('IS_DEBUG') && IS_DEBUG,
        $logger
    )
    )->run();
} catch (\Exception $ex) {
    $logger->log(LOG_ALERT, '', $ex);
}