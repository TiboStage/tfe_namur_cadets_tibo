<?php

namespace App\Service;

class VersionService
{
    public const VERSION = '0.9.0';

    public function getVersion(): string
    {
        return self::VERSION;
    }
}
