<?php

declare(strict_types=1);

require_once './config.php';
require_once './vendor/autoload.php';

/**
 * Custom error handler
 * @param string $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 * @return boolean true
 */
function errorHandler(string $errno, string $errstr, string $errfile, string $errline): bool
{
    if (!(error_reporting() & $errno)) {
        return false;
    }

    switch ($errno) {
        case E_USER_ERROR:
            __log(LOG_ERR, "ERROR($errno): $errstr in $errfile:$errline");
            exit(1);
        case E_USER_WARNING:
            __log(LOG_WARNING, "WARNING: $errstr in $errfile:$errline");
            break;
        case E_USER_NOTICE:
            __log(LOG_NOTICE, "NOTICE: $errstr in $errfile:$errline");
            break;
        default:
            __log(LOG_INFO, "UNKNOWN: $errstr in $errfile:$errline");
            break;
    }

    return true;
}

/**
 *
 * @param integer $type
 * @param string $message
 * @param \Exception $ex
 */
function __log(int $type, string $message, \Exception $ex = null): void
{
    if (!defined('LOG_LOCAL7')) {
        return;
    }
    $logIsOpened = openlog(IDENT, LOG_ODELAY, LOG_LOCAL7);
    if ($logIsOpened) {
        if ($ex instanceof \Exception) {
            syslog($type, $ex->getMessage());
            if (defined('IS_DEBUG') && IS_DEBUG) {
                echo "EXCEPTION: {$ex->getMessage()}" . PHP_EOL;
                syslog(LOG_INFO, $ex->getFile());
                syslog(LOG_INFO, $ex->getLine());
                syslog(LOG_INFO, $ex->getCode());
                syslog(LOG_INFO, $ex->getTraceAsString());
                echo "TRACE: {$ex->getTraceAsString()}" . PHP_EOL;
            }
        } else if ($message !== '') {
            syslog($type, $message);
        }

        closelog();
    }
}

try {
    $oldErrorHandler = set_error_handler("errorHandler");
    if (defined('IS_DEBUG') && IS_DEBUG) {
        require_once './testData.php';
    }
    $bot = new \Bots\TelegramSecurityExpertBot();
    $bot->registerCommand('/hashid', \Commands\HashIdCommand::class);
    $bot->execute();
} catch (\Exception $ex) {
    __log(LOG_ALERT, '', $ex);
    header('Content-Type: text/html; charset=utf-8');
}
