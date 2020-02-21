<?php

declare(strict_types=1);

use Bots\TelegramSecurityExpertBot;
use Commands\CommandListener;
use Commands\HashIdCommand;

require_once './config.php';
require_once './vendor/autoload.php';

if (defined('IS_DEBUG') && IS_DEBUG) {
    require_once './testData.php';
}

(new \Misc\Application(
    new TelegramSecurityExpertBot(
        (new CommandListener())
        ->addCommand('/hashid', new HashIdCommand())
    ),
    defined('IS_DEBUG') && IS_DEBUG
)
)->run();
