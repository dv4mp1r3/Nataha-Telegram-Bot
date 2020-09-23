<?php

namespace Misc\Input;

class PhpInputReader implements IReader
{

    public function readAll()
    {
        $file = "php://input";
        $content = file_get_contents($file);
        if ($content === false) {
            throw new \Exception("Error on file_get_contents for {$file}");
        }
        return $content;
    }
}