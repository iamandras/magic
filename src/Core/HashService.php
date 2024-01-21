<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class HashService
{
    public static function generateId(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }

    public static function generateLongId(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }
}
