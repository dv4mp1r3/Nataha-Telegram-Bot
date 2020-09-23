<?php

namespace Misc\Input;

class FileReader implements IReader
{
    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function readAll()
    {
        if (!file_exists($this->filePath)) {
            throw new \Exception("File {$this->filePath} doesn't exists");
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new \Exception("Error on file_get_contents for {$this->filePath}");
        }

        return $content;
    }
}