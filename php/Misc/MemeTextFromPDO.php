<?php

declare(strict_types=1);

namespace Misc;

use Bots\Exception;

/**
 * хелпер для получения случайных строчек для Misc/TelegramNeVsratoslavBotratoslavBot.php
 * структура таблицы для mysql:
 * CREATE TABLE `nevsratoslav` (
 * `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
 * `text` VARCHAR(500) NOT NULL DEFAULT '' COLLATE 'utf8_bin',
 * PRIMARY KEY (`id`),
 * UNIQUE INDEX `id` (`id`)
 * )
 * COLLATE='utf8_bin'
 * ENGINE=InnoDB
 * Class MemeTextTableFromPDO
 * @package Misc
 */
class MemeTextFromPDO implements TextGenerator
{

    private \PDO $pdo;

    private string $query;

    /**
     * @param \PDO $pdo инстанс PDO с переданным DSN
     * @param string $query запрос для получения текста из таблицы, который будет выполнен $pdo
     */
    public function __construct(\PDO $pdo, string $query)
    {
        $this->pdo = $pdo;
        $this->query = $query;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function genString(): string
    {
        $res = $this->pdo->query($this->query);
        if (count($res) !== 1) {
            throw new Exception('Empty data from ' . __METHOD__);
        }
        foreach ($res as $row) {
            return $row['text'];
        }
    }
}