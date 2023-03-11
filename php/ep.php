<?php

declare(strict_types=1);

use Bots\TelegramNeVsratoslavBot;
use Misc\MemeTextFromPDO;
use pbot\Commands\CommandListener;
use Commands\HashIdCommand;
use pbot\Misc\Application;
use pbot\Misc\Logger;
use pbot\Misc\Input\FileReader;
use pbot\Misc\Input\PhpInputReader;

require_once './config.php';
require_once './vendor/autoload.php';

$reader = defined('IS_DEBUG') && IS_DEBUG
    ? (new FileReader(__DIR__.'/input/text_chat.json'))
    : (new PhpInputReader());
$db = new \PDO(PDO_MEME_DSN);
$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$logger = new \Misc\EchoLogger();
try{
    (new Application(
        (new TelegramNeVsratoslavBot(
            $reader,
            (new CommandListener())
                ->addCommand('/hashid', new HashIdCommand())
        ))->setFontPath(__DIR__.'/lobster.ttf')
            ->setMaxWordsCount(intval(getenv('MAX_WORDS_COUNT')))
            ->setTextGenerator(new MemeTextFromPDO($db, PDO_MEME_QUERY)),
        $logger,
        defined('IS_DEBUG') && IS_DEBUG
    )
    )->run();
}
catch(\Exception $ex)
{
    $logger->log(LOG_ALERT, '', $ex);
}

