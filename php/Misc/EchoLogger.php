<?php

declare(strict_types=1);

namespace Misc;


use pbot\Misc\Logger;

class EchoLogger extends Logger
{
    /**
     * @inheritDoc
     */
    public function log(int $type, string $message, \Exception $ex = null): void
    {
        if ($ex instanceof \Exception) {
            echo "EXCEPTION: {$ex->getMessage()}" . PHP_EOL;
            echo $ex->getFile() . ":". (string)$ex->getLine() . PHP_EOL;
            echo $ex->getCode(). PHP_EOL;
            echo $ex->getTraceAsString() . PHP_EOL;
            echo "TRACE: {$ex->getTraceAsString()}" . PHP_EOL;
        }
        echo "MESSAGE: {$message}" . PHP_EOL;

    }
}