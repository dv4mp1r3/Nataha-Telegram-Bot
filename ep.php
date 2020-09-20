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
(new Application(
    (new TelegramNeVsratoslavBot(
        (new CommandListener())
        ->addCommand('/hashid', new HashIdCommand()),
        $reader
    ))->setFontPath(__DIR__.'/lobster.ttf'),
    defined('IS_DEBUG') && IS_DEBUG
)
)->run();
