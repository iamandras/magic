<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class MagicResponse
{
    public function __construct(
        public string $content,
        public int $httpCode = 200,
        public string $contentType = 'application/json',
    ) {
    }
}
