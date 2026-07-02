<?php

namespace App\Service;

class VersionService
{
    public const string VERSION = '1.0.0';

    public function getVersion(): string
    {
        return self::VERSION;
    }
}
