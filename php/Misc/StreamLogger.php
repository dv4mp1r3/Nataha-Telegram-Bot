<?php

declare(strict_types=1);

namespace Misc;

use pbot\Misc\Logger;

const STREAM_STDOUT = 0;
const STREAM_STDERR = 1;

class StreamLogger extends Logger
{
    public function log(int $type, string $message, \Exception $ex = null): void
    {
        $file = "php://stdout";
        if ($type === STREAM_STDERR) {
            $file = "php://stderr";
        }
        if (mb_strlen($message) > 0) {
            file_put_contents($file, $message );
        }

        if ($ex !== null) {
            $exceptionMessage = $ex->getMessage().PHP_EOL
                .$ex->getFile().':'.$ex->getLine().PHP_EOL
                .$ex->getTraceAsString();
            file_put_contents($file, $exceptionMessage);
        }
    }

}