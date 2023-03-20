<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DbColumn
{
    public const TYPE_STRING = 'string';
    public const TYPE_DATETIME = 'datetime';

    public function __construct(
        public string $columnType,
        public bool $nullable = false,
    ) {
    }
}