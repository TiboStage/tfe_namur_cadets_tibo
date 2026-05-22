<?php

namespace App\Service;

class VersionService
{
    public const VERSION = '0.1.0';

    public function getVersion(): string
    {
        return self::VERSION;
    }
}
