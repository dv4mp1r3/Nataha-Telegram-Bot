<?php

declare(strict_types=1);

namespace ResourceParsers;

abstract class AbstractResourceParser implements IResourceParser
{
    protected string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public abstract function parse() : array;
}