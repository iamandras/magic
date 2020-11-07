<?php

declare(strict_types=1);

namespace MagicFramework\Core;

interface CommandInterface
{
    public function process(?string $parameter): void;

    public function getCommandName(): string;
}
