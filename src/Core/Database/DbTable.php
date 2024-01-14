<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

#[\Attribute(\Attribute::TARGET_CLASS)]
class DbTable
{
    public function __construct(
        public string $name,
        public string $format,
    ) {
    }
}