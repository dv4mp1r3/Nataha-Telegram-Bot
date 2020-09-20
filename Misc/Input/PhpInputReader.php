<?php

declare(strict_types=1);

namespace Misc\Input;

class PhpInputReader implements IReader
{

    public function readAll(): string
    {
        $file = "php://input";
        $content = file_get_contents($file);
        if ($content === false) {
            throw new \Exception("Error on file_get_contents for {$file}");
        }
        return $content;
    }
}