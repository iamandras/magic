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
        $useAttributes = false;
        if (count($classAttributes) > 0 && $classAttributes[0]->getName() === DbTable::class) {
            $useAttributes = true;
        }
        $vars = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $entityProperties = [];

        if ($useAttributes) {
            foreach ($vars as $var) {
                $attributes = $var->getAttributes();
                if (count($attributes) === 0) {
                    continue;
                }
                $attr = $attributes[0];
                if ($attr->getName() !== DbColumn::class) {
                    continue;
                }
                $arguments = $attr->getArguments();

                $entityProperties[] = new EntityProperty($var->getName(), $arguments['columnType'], $arguments['nullable']);
            }

            return $entityProperties;
        }

        $entityProperties = [];
        foreach ($vars as $var) {
            $attributes = $var->getAttributes();
            if (count($attributes) > 0) {
                echo $attributes[0]->getName();
            }
            $entityProperties[] = $this->createProperty($var);
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
