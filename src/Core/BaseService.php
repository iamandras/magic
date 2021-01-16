<?php

declare(strict_types=1);

namespace MagicFramework\Core;

use Exception;
use MagicFramework\Core\Database\PDOLayer;

class BaseService
{
    protected PDOLayer $dbLayer;

    public function __construct(PDOLayer $dbLayer)
    {
        $this->dbLayer = $dbLayer;
    }

    /**
     * @throws ApiException
     */
    public function handleException(Exception $e): void
    {
        $this->dbLayer->rollback();
        $apiException = new ApiException(ApiException::INTERNAL_ERROR);
        $apiException->setException($e);
        throw $apiException;
    }

    public function beginTransaction(): void
    {
        $this->dbLayer->beginTransaction();
    }

    public function commit(): void
    {
        $this->dbLayer->commit();
    }
}