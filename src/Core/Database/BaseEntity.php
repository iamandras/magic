<?php

declare(strict_types=1);

namespace MagicFramework\Core\Database;

use DateTime;
use ReflectionClass;
use ReflectionProperty;

class BaseEntity
{
    public string $id;
    public bool $fromDatabase = false;

    /**
     * @return string[]
     */
    public function getSkippedPropertiesFromOutput(): array
    {
        return [];
    }

    private function createProperty(ReflectionProperty $reflectionProperty): EntityProperty
    {
        $docComment = $reflectionProperty->getDocComment();
        $docComment = str_replace(' ', '', $docComment);
        $docComment = str_replace('/**', '', $docComment);
        $docComment = str_replace('*/', '', $docComment);
        $docComment = str_replace('@var', '', $docComment);
        $docComment = str_replace('\\', '', $docComment);

        $nullable = false;

        if (strpos($docComment, '|null') !== false) {
            $docComment = str_replace('|null', '', $docComment);
            $nullable = true;
        }

        return new EntityProperty($reflectionProperty->getName(), $docComment, $nullable);
    }

    /**
     * @return EntityProperty[]
     */
    public function getEntityProperties(): array
    {
        $reflection = new ReflectionClass(get_class($this));
        $classAttributes = $reflection->getAttributes();
        $format = '7.1';
        foreach ($classAttributes as $classAttribute) {
            if ($classAttribute->getName() === DbTable::class) {
                if (array_key_exists('format', $classAttribute->getArguments())) {
                    $format = $classAttribute->getArguments()['format'];
                    break;
                }
            }
        }

        $entityProperties = [];

        if ($format === '7.1') {
            $vars = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

            foreach ($vars as $var) {
                if (in_array($var->getName(), ['fromDatabase'])) {
                    continue;
                }

                $entityProperties[] = $this->createProperty($var);
            }
        }

        if ($format === '8.2') {
            $properties = $reflection->getProperties();

            // ID needs to be added
            $entityProperties[] = new EntityProperty(
                name: 'id',
                type: DbColumn::TYPE_STRING,
                nullable: false,
            );

            foreach ($properties as $property) {
                $propAttributes = $property->getAttributes(DbColumn::class);

                foreach ($propAttributes as $propAttribute) {
                    $nullable = false;
                    if (array_key_exists('nullable', $propAttribute->getArguments())) {
                        $nullable = $propAttribute->getArguments()['nullable'];
                    }

                    $entityProperties[] = new EntityProperty(
                        name: $property->getName(),
                        type: $propAttribute->getArguments()['type'],
                        nullable: $nullable,
                    );
                }
            }
        }

        return $entityProperties;
    }

    public function getArrayForJson(array $parameters = []): array
    {
        $skippedProperties = $this->getSkippedPropertiesFromOutput();
        $entityProperties = $this->getEntityProperties();
        $output = [];
        foreach ($entityProperties as $entityProperty) {
            $value = null;
            $propertyName = $entityProperty->name;
            if (in_array($propertyName, $skippedProperties)) {
                continue;
            }
            if (isset($this->{$propertyName})) {
                $value = $this->{$propertyName};
            }

            if ($entityProperty->type === EntityProperty::TYPE_DATETIME) {
                $output[$propertyName] = $this->convertDateToIso8601($value);
                continue;
            }

            $output[$propertyName] = $value;
        }

        return $output;
    }

    public function getUtcTimestamp(): int
    {
        $date = new DateTime('now', new \DateTimeZone('UTC'));

        return $date->getTimestamp();
    }

    public function convertDateToIso8601(?DateTime $dateTime): ?string
    {
        if ($dateTime === null) {
            return null;
        }

        return $dateTime->format('Y-m-d\TH:i:sP');
    }
}
