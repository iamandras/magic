<?php

declare(strict_types=1);

namespace MagicFramework\Core;

use Exception;

class BaseService
{
    /**
     * @throws ApiException
     */
    protected function handleException(Exception $e): void
    {
        $apiException = new ApiException(ApiException::INTERNAL_ERROR);
        $apiException->setException($e);
        throw $apiException;
    }
}