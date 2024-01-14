<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class DbColumn
{
    public const TYPE_INT = 'int';
    public const TYPE_STRING = 'string';
    public const TYPE_DATETIME = 'DateTime';
    public const TYPE_BOOLEAN = 'bool';

    public function __construct(
        public string $type,
        public bool $nullable = false,
    ) {
    }
}