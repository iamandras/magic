<?php

declare(strict_types=1);

namespace MagicFramework\Core;

use Exception;
use MagicFramework\Core\Database\PDOLayer;

class BaseService
{
    public function __construct(protected PDOLayer $pdoLayer)
    {
    }

    /**
     * @throws ApiException
     */
    public function handleException(Exception $e): void
    {
        $this->pdoLayer->rollback();
        throw $e;
    }

    public function beginTransaction(): void
    {
        $this->pdoLayer->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdoLayer->commit();
    }
}