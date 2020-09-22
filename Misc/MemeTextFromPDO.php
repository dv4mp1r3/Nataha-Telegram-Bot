<?php

declare(strict_types=1);

namespace Misc;

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
class MemeTextFromPDO
{

    /**
     * @param \PDO $pdo инстанс PDO с переданным DSN
     * @param string $query запрос для получения текста из таблицы, который будет выполнен $pdo
     * @return string результат выполнения запроса $query для инстанса $pdo
     * @throws \Exception
     */
    public static function getRandomString(\PDO $pdo, string $query): string
    {
        $res = $pdo->query($query);
        if (count($res) !== 1) {
            throw new \Exception('Empty data from ' . __METHOD__);
        }
        foreach ($res as $row) {
            return $row['text'];
        }
    }

}