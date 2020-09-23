<?php

namespace Commands;


interface ICommand
{
    /**
     * Метод выполнения команды
     * @param array $args массив аргументов команды
     * @param array $decodedInput массив декодированного из php://input сообщения
     * Смотрим конкретного бота чтобы посмотреть как именно сообщение разделяется на аргументы
     * и как декодируется сообщение
     * @return string результат выполнения
     */
    public function run($args, $decodedInput = []);
}