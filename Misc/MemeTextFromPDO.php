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