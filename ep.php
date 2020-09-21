<?php

declare(strict_types=1);

use Bots\TelegramNeVsratoslavBot;
use Commands\CommandListener;
use Commands\HashIdCommand;
use Misc\Application;
use Misc\Input\FileReader;
use Misc\Input\PhpInputReader;

require_once './config.php';
require_once './vendor/autoload.php';

$reader = defined('IS_DEBUG') && IS_DEBUG
    ? (new FileReader(__DIR__.'/input/text_chat.json'))
    : (new PhpInputReader());
$db = new \PDO(PDO_MEME_DSN);
$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
(new Application(
    (new TelegramNeVsratoslavBot(
        (new CommandListener())
        ->addCommand('/hashid', new HashIdCommand()),
        $reader
    ))->setFontPath(__DIR__.'/lobster.ttf')
        ->setMemeTextQuery(PDO_MEME_QUERY)
        ->setMemTextPdo($db),
    defined('IS_DEBUG') && IS_DEBUG
)
)->run();
