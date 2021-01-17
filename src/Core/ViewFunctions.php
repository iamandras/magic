<?php

function getValue($entity, $key): string
{
    if (!isset($entity)) {
        return '';
    }

    if (!property_exists($entity, $key)) {
        return '';
    }

    if (is_bool($entity->{$key})) {
        return $entity->{$key} ? '1' : '0';
    }

    return $entity->{$key};
}

?>