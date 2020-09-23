<?php

namespace ResourceParsers;

abstract class AbstractResourceParser implements IResourceParser
{
    protected $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public abstract function parse();
}