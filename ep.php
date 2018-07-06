<?php

define('IDENT', 'dc7812flood_reception_bot');
define('IS_DEBUG', true);
define('MAX_MESSAGE_LENGTH', 300);
define('FLOCK_SLEEP_INTERVAL', 500000);
define('MAX_DB_READ_TRY', 15);
define('AVAILABLE_CHAT_ID', '1111111');

/**
 * Custom error handler
 * @param string $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 * @return boolean true
 */
function errorHandler($errno, $errstr, $errfile, $errline)
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
 * @param type $type
 * @param string $message
 * @param \Exception $ex
 */
function __log($type, $message, $ex = null)
{
    $logIsOpened = openlog(IDENT, LOG_ODELAY, LOG_LOCAL7);
    if ($logIsOpened)
    {
        if ($ex instanceof \Exception)
        {
            syslog($type, $ex->getMessage());
            if (defined('IS_DEBUG') && IS_DEBUG)
            {
                echo "EXCEPTION: {$ex->getMessage()}".PHP_EOL;
                syslog(LOG_INFO, $ex->getFile());
                syslog(LOG_INFO, $ex->getLine());
                syslog(LOG_INFO, $ex->getCode());
                syslog(LOG_INFO, $ex->getTraceAsString());
                echo "TRACE: {$ex->getTraceAsString()}".PHP_EOL;
            }
        }
        else
        {
            syslog($type, $message);
        }
        
        closelog();
    }
}

try
{
    $oldErrorHandler = set_error_handler("errorHandler");
    if (defined('IS_DEBUG') && IS_DEBUG)
    {
        require_once './testData.php';
    }
    require_once './bot-json.php';
} catch (\Exception $ex) 
{
    __log(LOG_ALERT, null, $ex);
}
