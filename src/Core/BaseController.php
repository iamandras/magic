<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class BaseController
{
    protected function getQueryParam(string $paramName, $defaultValue = null): ?string
    {
        if (!$this->hasQueryParam($paramName)) {
            return $defaultValue;
        }

        return filter_var($_GET[$paramName], FILTER_SANITIZE_STRING);
    }

    protected function getPostParam(string $paramName, $defaultValue = null): ?string
    {
        if (!$this->hasPostParam($paramName)) {
            return $defaultValue;
        }

        return filter_var($_POST[$paramName], FILTER_SANITIZE_STRING);
    }

    protected function returnHtml(string $html): MagicResponse
    {
        return new MagicResponse($html, 200, 'text/html');
    }

    protected function hasQueryParam(string $paramName): bool
    {
        return isset($_GET[$paramName]);
    }

    protected function hasPostParam(string $paramName): bool
    {
        return isset($_POST[$paramName]);
    }

    protected function getPayload(): array
    {
        $payload = file_get_contents('php://input');

        $payloadArray = json_decode($payload, true);
        if ($payloadArray === null) {
            throw new ApiException(ApiException::ERROR_MALFORMED_PAYLOAD);
        }

        return $payloadArray;
    }
}
