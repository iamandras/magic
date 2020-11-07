<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;

class MongoLayer
{
    private Client $mongoClient;

    public function __construct()
    {
        $this->mongoClient = new Client(constant('MONGO_URL'));
    }

    public function getCollection(string $dbName, string $collectionName): Collection
    {
        return $this->mongoClient->selectCollection($dbName, $collectionName);
    }

    public function findRecords(string $dbName, string $collectionName, array $query = [], int $limit = 0): Cursor
    {
        $collection = $this->getCollection($dbName, $collectionName);
        $options = [];
        if ($limit != 0) {
            $options['limit'] = $limit;
        }

        return $collection->find($query, $options);
    }

    public function countRecords(string $dbName, string $collectionName, array $query = []): int
    {
        $collection = $this->getCollection($dbName, $collectionName);

        return $collection->countDocuments($query);
    }

    public function findRecord(string $dbName, string $collectionName, array $query): ?array
    {
        $collection = $this->getCollection($dbName, $collectionName);

        $data = $collection->findOne($query);
        if ($data === null) {
            return null;
        }

        unset($data['_id']);

        return json_decode(json_encode($data), true);
    }

    public function insertRecord(string $dbName, string $collectionName, array $recordData): void
    {
        $collection = $this->getCollection($dbName, $collectionName);

        $collection->insertOne($recordData);
    }

    public function updateRecord(
        string $dbName,
        string $collectionName,
        array $recordData,
        string $idFieldName = 'recordId'
    ): void {
        $collection = $this->getCollection($dbName, $collectionName);

        $collection->replaceOne([ $idFieldName => $recordData[$idFieldName]], $recordData);
    }
}