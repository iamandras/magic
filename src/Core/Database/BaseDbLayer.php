<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

class BaseDbLayer
{
    protected PDOLayer $dbLayer;

    public function __construct(PDOLayer $dbLayer)
    {
        $this->dbLayer = $dbLayer;
    }

    public function beginTransaction(): void
    {
        $this->dbLayer->beginTransaction();
    }

    public function commit(): void
    {
        $this->dbLayer->commit();
    }

    public function rollback(): void
    {
        $this->dbLayer->rollback();
    }
}