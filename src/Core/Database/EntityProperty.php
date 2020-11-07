<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

class EntityProperty
{
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'int';
    public const TYPE_DATETIME = 'DateTime';
    public const TYPE_BOOLEAN = 'bool';

    /** @var string */
    public $name;

    /** @var string */
    public $type;

    /** @var bool */
    public $nullable;

    public function __construct(string $name, string $type, bool $nullable)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
    }
}
