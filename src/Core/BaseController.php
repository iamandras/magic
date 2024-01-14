<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class BaseController
{
    protected function getQueryParam(string $paramName, mixed $defaultValue = null): ?string
    {
        if (!$this->hasQueryParam($paramName)) {
            return $defaultValue;
        }

        return htmlspecialchars($_GET[$paramName], ENT_QUOTES);
    }

    protected function getPostParam(string $paramName, mixed $defaultValue = null): ?string
    {
        if (!$this->hasPostParam($paramName)) {
            return $defaultValue;
        }

        return htmlspecialchars($_POST[$paramName], ENT_QUOTES);
    }

    protected function getNumberPostParam(string $paramName, mixed $defaultValue = null): ?int
    {
        if (!$this->hasPostParam($paramName)) {
            return $defaultValue;
        }

        return intval($_POST[$paramName]);
    }

    protected function getNumberQueryParam(string $paramName, int $defaultValue = 0): ?int
    {
        $queryParam = $this->getQueryParam($paramName, $defaultValue);

        return intval($queryParam);
    }

    protected function returnHtml(string $html): MagicResponse
    {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

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

    /**
     * @param string[] $fields
     * @throws ApiException
     */
    protected function requiredPostFields(array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            if ($this->getPostParam($field, '') === '') {
                $errors[$field] = 'required';
            }
        }

        if (count(array_keys($errors)) !== 0) {
            throw new ApiException(
                ApiException::ERROR_VALIDATION_PROBLEMS,
                $errors
            );
        }
    }


    protected function redirect(string $path): MagicResponse
    {
        $response = [
            'redirect' => $path
        ];

        return new MagicResponse(json_encode($response));
    }
}
