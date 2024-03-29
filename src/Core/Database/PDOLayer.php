<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

use DateTime;
use DateTimeZone;
use Exception;
use PDO;

class PDOLayer
{
    private PDO $connection;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        $this->connection = new PDO(constant('DB'), constant('DB_USER'), constant('DB_PASSWORD'));
        $this->connection->exec(
            "SET character_set_results = 'utf8', " .
            "character_set_client = 'utf8', character_set_connection = 'utf8', " .
            "character_set_database = 'utf8', character_set_server = 'utf8', autocommit=0"
        );
        $this->connection->exec("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");

        $this->connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * @param EntityProperty[] $entityProperties
     */
    private function setEntityFromDatabase(
        BaseEntity $entity,
        array $entityProperties,
        array $record,
    ): BaseEntity {
        foreach ($entityProperties as $entityProperty) {
            $recordValue = null;
            $propertyName = $entityProperty->name;
            if (isset($record[$propertyName])) {
                $recordValue = $record[$propertyName];
            } else {
                if (!$entityProperty->nullable) {
                    throw new Exception($entityProperty->name . ' is not nullable');
                }
            }

            switch ($entityProperty->type) {
                case EntityProperty::TYPE_STRING:
                    $entity->{$propertyName} = $recordValue === null ? null : (string)$recordValue;
                    break;
                case EntityProperty::TYPE_INT:
                    $entity->{$propertyName} = (int)$recordValue;
                    break;
                case EntityProperty::TYPE_DATETIME:
                    if ($recordValue !== null) {
                        $entity->{$propertyName} = new DateTime($recordValue, new DateTimeZone('UTC'));
                    }
                    break;
                case EntityProperty::TYPE_BOOLEAN:
                    $entity->{$propertyName} = $recordValue === 1;
                    break;
            }
        }

        return $entity;
    }

    public function addOrderBy(string $column, string $direction, array $allowedColumns): string
    {
        if (!in_array($column, $allowedColumns)) {
            return '';
        }

        return ' ORDER BY ' . $column . ' ' . $direction;
    }

    public function getRecords(
        string $sql,
        string $entityClass,
        array $parameters,
        int $limit = null,
        int $offset = null
    ): array {
        $tempEntity = new $entityClass;
        $entityProperties = $tempEntity->getEntityProperties();

        $limitSql = '';
        if ($limit !== null) {
            $limitSql = ' LIMIT ' . $limit;
            if ($offset !== null) {
                $limitSql .= ' OFFSET ' . $offset;
            }
        }

        $stmt = $this->connection->prepare($sql . $limitSql);
        $stmt->execute($parameters);

        $list = [];
        while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var BaseEntity $entity */
            $entity = new $entityClass;
            $entity->fromDatabase = true;
            $list[] = $this->setEntityFromDatabase($entity, $entityProperties, $record);
        }

        return $list;
    }

    public function getSingularIntValue(string $sql, array $parameters): ?int
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($parameters);

        return (int)$stmt->fetchColumn();
    }

    public function getRecord(string $sql, string $entityClass, array $parameters): ?BaseEntity
    {
        $tempEntity = new $entityClass;
        $entityProperties = $tempEntity->getEntityProperties();

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($parameters);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_bool($record) && !$record) {
            return null;
        }

        /** @var BaseEntity $entity */
        $entity = new $entityClass;
        $entity->fromDatabase = true;

        return $this->setEntityFromDatabase($entity, $entityProperties, $record);
    }

    private function updateRecord(BaseEntity $entity, string $tableName): void
    {
        $entityProperties = $entity->getEntityProperties();

        $fields = [];
        $parameters = [];

        foreach ($entityProperties as $entityProperty) {
            $propertyName = $entityProperty->name;

            if ($propertyName === 'id') {
                continue;
            }

            $value = null;
            if (isset($entity->{$propertyName})) {
                $value = $entity->{$propertyName};
            }

            if (is_null($value)) {
                $fields[] = $propertyName . ' = null';
                continue;
            }

            if ($entityProperty->type === EntityProperty::TYPE_DATETIME) {
                $fields[] = $propertyName . ' = ?';
                $parameters[] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if ($entityProperty->type === EntityProperty::TYPE_BOOLEAN) {
                $fields[] = $propertyName . ' = ?';
                $parameters[] = $value ? 1 : 0;
                continue;
            }

            $fields[] = $propertyName . ' = ?';
            $parameters[] = $value;
        }

        $parameters[] = $entity->id;

        $sql = 'UPDATE ' . $tableName .
            ' SET ' . implode(',', $fields) .
            ' WHERE id = ?';

        $stmt= $this->connection->prepare($sql);
        $stmt->execute($parameters);
    }

    private function insertRecord(BaseEntity $entity, string $tableName): void
    {
        $entityProperties = $entity->getEntityProperties();
        $fields = [];
        $fieldValues = [];
        $parameters = [];

        foreach ($entityProperties as $entityProperty) {
            $propertyName = $entityProperty->name;

            $fields[] = $propertyName;

            $value = null;
            if (isset($entity->{$propertyName})) {
                $value = $entity->{$propertyName};
            }

            if (is_null($value)) {
                $fieldValues[] = 'null';
                continue;
            }

            if ($entityProperty->type === EntityProperty::TYPE_DATETIME) {
                $fieldValues[] = '?';
                $parameters[] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if ($entityProperty->type === EntityProperty::TYPE_BOOLEAN) {
                $fieldValues[] = '?';
                $parameters[] = $value ? 1 : 0;
                continue;
            }

            $fieldValues[] = '?';
            $parameters[] = $value;
        }

        $sql = 'INSERT INTO ' . $tableName .
            ' (' . implode(',', $fields) .
            ') VALUES(' . implode(',', $fieldValues) . ')';

        $stmt= $this->connection->prepare($sql);
        $stmt->execute($parameters);
        $entity->fromDatabase = true;
    }

    public function saveRecord(BaseEntity $entity, string $tableName): void
    {
        if ($entity->fromDatabase) {
            $this->updateRecord($entity, $tableName);
            return;
        }

        $this->insertRecord($entity, $tableName);
    }

    public function removeRecords(string $tableName, string $whereClause, array $parameters): void
    {
        $sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $whereClause;
        $stmt= $this->connection->prepare($sql);
        $stmt->execute($parameters);
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

    public function select(string $sql, $bindValues = array(), $fetchMode = PDO::FETCH_OBJ) {
        $stmt = $this->connection->prepare($sql);
        foreach ($bindValues as $key => $value) {
            $stmt->bindValue("$key", $value);
        }

        $stmt->execute();

        return $stmt->fetchAll($fetchMode);
    }

    public function executeSql(string $sql, array $parameters = []): void
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($parameters);
    }

    public function count(string $sql, $bindValues = array()): int
    {
        $stmt = $this->connection->prepare($sql);
        foreach ($bindValues as $key => $value) {
            $stmt->bindValue("$key", $value);
        }

        $stmt->execute();

        return $stmt->fetchColumn();
    }
}
