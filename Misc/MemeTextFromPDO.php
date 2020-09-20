<?php

declare(strict_types=1);

namespace Misc;

/**
 * хелпер для получения случайных строчек для Misc/TelegramNeVsratoslavBotratoslavBot.php
 * структура таблицы для mysql:
 * CREATE TABLE `nevsratoslav` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `text` VARCHAR(500) NOT NULL DEFAULT '' COLLATE 'utf8_bin',
    PRIMARY KEY (`id`),
    UNIQUE INDEX `id` (`id`)
    )
    COLLATE='utf8_bin'
    ENGINE=InnoDB
 * Class MemeTextTableFromPDO
 * @package Misc
 */
class MemeTextTableFromPDO
{

    public static function getRandomString(\PDO $pdo, string $tableName) : string
    {
        $key = ':table';
        $st= $pdo->prepare("SELECT text FROM {$key} ORDER BY RAND() LIMIT 1");
        $st->execute([$key => $tableName]);
        $data = $st->fetchAll(\PDO::FETCH_ASSOC);
        if (count($data) !== 1)
        {
            throw new \Exception('Empty data from '.__METHOD__);
        }
        return $data['text'];
    }

}