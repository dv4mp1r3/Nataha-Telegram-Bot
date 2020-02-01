<?php

declare(strict_types=1);

namespace Commands;


class CommandListener
{
    protected $registeredCommands = [];

    /**
     * @var ICommand
     */
    protected $foundCommand;

    /**
     * @var array
     */
    protected $commandArgs;

    public function addCommand(string $commandText, ICommand $command): CommandListener
    {
        $this->registeredCommands [$commandText] = $command;
        return $this;
    }

    public function isCommand(string $messageText): bool
    {
        foreach ($this->registeredCommands as $commandName => $commandObject) {
            if (mb_stripos($messageText, $commandName) === 0) {
                $this->foundCommand = $commandObject;
                $this->commandArgs = $this->parseCommandArgs($messageText, $commandName);
                return true;
            }
        }
        return false;
    }

    public function parseCommandArgs(string $messageText, string $commandName): array
    {
        $argsString = mb_strcut($messageText, mb_strlen($commandName));
        return array_values(array_filter($this->mbExplode(' ', $argsString)));
    }

    public function executeFoundCommand(): string
    {
        return $this->foundCommand->run($this->commandArgs);
    }

    /**
     * A cross between mb_split and preg_split, adding the preg_split flags
     * to mb_split.
     * @param string $pattern
     * @param string $string
     * @param int $limit
     * @param int $flags
     * @return array
     * @see https://github.com/vanderlee/PHP-multibyte-functions/blob/master/functions/mb_explode.php
     */
    protected function mbExplode($pattern, $string, $limit = -1, $flags = 0)
    {
        $strlen = strlen($string);  // bytes!

        if (!$strlen) {
            return array('');
        }

        mb_ereg_search_init($string);

        $lengths = array();
        $position = 0;
        while (($array = mb_ereg_search_pos($pattern)) !== false) {
            // capture split
            $lengths[] = array($array[0] - $position, false, null);

            // move position
            $position = $array[0] + $array[1];

            // capture delimiter
            $regs = mb_ereg_search_getregs();
            $lengths[] = array($array[1], true, isset($regs[1]) && $regs[1]);

            // Continue on?
            if ($position >= $strlen) {
                break;
            }
        }

        // Add last bit, if not ending with split
        $lengths[] = array($strlen - $position, false, null);

        // Substrings
        $parts = array();
        $position = 0;
        $count = 1;
        foreach ($lengths as $length) {
            $is_delimiter = $length[1];
            $is_captured = $length[2];

            if ($limit > 0 && !$is_delimiter && ($length[0] || ~$flags & PREG_SPLIT_NO_EMPTY) && ++$count > $limit) {
                if ($length[0] > 0 || ~$flags & PREG_SPLIT_NO_EMPTY) {
                    $parts[] = $flags & PREG_SPLIT_OFFSET_CAPTURE ? array(mb_strcut($string, $position), $position) : mb_strcut($string, $position);
                }
                break;
            } elseif ((!$is_delimiter || ($flags & PREG_SPLIT_DELIM_CAPTURE && $is_captured)) && ($length[0] || ~$flags & PREG_SPLIT_NO_EMPTY)) {
                $parts[] = $flags & PREG_SPLIT_OFFSET_CAPTURE ? array(mb_strcut($string, $position, $length[0]), $position) : mb_strcut($string, $position, $length[0]);
            }

            $position += $length[0];
        }

        return $parts;
    }

}