<?php

declare(strict_types=1);

namespace MagicFramework\Core;

use MagicFramework\Core\Database\BaseEntity;

class MagicResponseEntityArray extends MagicResponse
{
    /**
     * @param BaseEntity[] $entities
     */
    public function __construct(public array $entities)
    {
        $result = [];
        foreach ($this->entities as $entity) {
            $result[] = $entity->getArrayForJson();
        }

        parent::__construct(json_encode($result));
    }
}
