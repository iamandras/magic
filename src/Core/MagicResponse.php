<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class MagicResponse
{
    public function __construct(
        private string $content,
        private int $httpCode = 200,
        private string $contentType = 'application/json'
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function replaceContent(string $search, string $replace): void
    {
        $this->content = str_replace($search, $replace, $this->content);
    }
}
