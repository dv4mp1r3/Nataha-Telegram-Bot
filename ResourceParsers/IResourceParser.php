<?php

declare(strict_types=1);

namespace ResourceParsers;

interface IResourceParser{

    public function parse() : array;
}