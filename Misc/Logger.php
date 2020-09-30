<?php

namespace Misc;

class Logger
{
    const ERROR_HANDLER_FUNCTION = 'errorHandler';

    /**
     * Custom error handler
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return boolean true
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        switch ($errno) {
            case E_USER_ERROR:
                $this->log(LOG_ERR, "ERROR($errno): $errstr in $errfile:$errline");
                exit(1);
            case E_USER_WARNING:
                $this->log(LOG_WARNING, "WARNING: $errstr in $errfile:$errline");
                break;
            case E_USER_NOTICE:
                $this->log(LOG_NOTICE, "NOTICE: $errstr in $errfile:$errline");
                break;
            default:
                $this->log(LOG_INFO, "UNKNOWN: $errstr in $errfile:$errline");
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
    public function log($type, $message, $ex = null)
    {
        if (!defined('LOG_LOCAL7')) {
            return;
        }
        $logIsOpened = openlog(IDENT, LOG_ODELAY, LOG_LOCAL7);
        if ($logIsOpened) {
            if ($ex instanceof \Exception) {
                syslog($type, $ex->getMessage());
                if ($this->isDebug) {
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
}