<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class ValidationError
{
    const ERROR_DUPLICATED_FIELD = 'duplicated_field';
    const ERROR_MISSING_FIELD_FOR_VALIDATION = 'missing_field_for_validation';
    const ERROR_MISSING_FIELD_FOR_VISUAL = 'missing_field_for_visual';
    const ERROR_RESERVED_FIELD_CODE = 'reserved_field_code';
    const ERROR_MISSING_PARENT_OBJECT_TYPE = 'missing_parent_object_type';
    const ERROR_INVALID_SINGLETON = 'invalid_singleton';
    const ERROR_INVALID_ABSTRACT = 'invalid_abstract';

    const ERROR_REQUIRED = 'required';
    const ERROR_WRONG_TYPE = 'wrong_type';
    const ERROR_NOT_ALLOWED_FIELD = 'not_allowed_field';
    const ERROR_NOT_ALLOWED_GROUP_KEY = 'not_allowed_group_key';
    const ERROR_TOO_SHORT_STRING = 'too_short_string';
    const ERROR_TOO_LONG_STRING = 'too_long_string';
    const ERROR_TOO_BIG_NUMBER = 'too_big_number';
    const ERROR_TOO_SMALL_NUMBER = 'too_small_number';
    const ERROR_TOO_BIG_ARRAY = 'too_big_array';
    const ERROR_TOO_SMALL_ARRAY = 'too_small_array';
    const ERROR_NOT_ALLOWED_VALUE = 'not_allowed_value';


    /** @var string */
    protected $id;

    /** @var string */
    protected $fieldCode;

    /** @var string */
    protected $error;

    /** @var string|null */
    protected $parentField;

    /** @var integer|null */
    protected $idx;

    /** @var array */
    protected $parameters;

    public function __construct(
        string $fieldCode,
        string $error,
        array $parameters = [],
        string $parentField = null,
        int $idx = null
    ) {
        $this->fieldCode = $fieldCode;
        $this->error = $error;
        $this->parameters = $parameters;

        if (isset($parentField)) {
            $this->parentField = $parentField;
        }

        $this->idx = $idx;
        $this->generateId();
    }

    public function generateArray(): array
    {
        $data = [
            "fieldCode" => $this->fieldCode,
            "error" => $this->error
        ];

        if ($this->parentField != null) {
            $data['parentField'] = $this->parentField;
        }

        if (isset($this->idx)) {
            $data['idx'] = $this->idx;
        }

        if (isset($this->parameters) && count($this->parameters) > 0) {
            $data['parameters'] = $this->parameters;
        }

        return $data;
    }


    protected function generateId(): void
    {
        $this->id = md5($this->fieldCode . $this->error . json_encode($this->parameters));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFieldCode(): string
    {
        return $this->fieldCode;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParentField(): ?string
    {
        return $this->parentField;
    }

    public function getIdx(): ?int
    {
        return $this->idx;
    }
}
