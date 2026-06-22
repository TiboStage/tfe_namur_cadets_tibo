<?php

namespace App\Service;

class VersionService
{
    public const VERSION = '1.0.0';

    public function getVersion(): string
    {
        return self::VERSION;
    }
}
