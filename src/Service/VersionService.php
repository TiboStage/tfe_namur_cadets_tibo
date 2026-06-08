<?php

namespace App\Service;

class VersionService
{
    public const VERSION = '0.8.0';

    public function getVersion(): string
    {
        return self::VERSION;
    }
}
