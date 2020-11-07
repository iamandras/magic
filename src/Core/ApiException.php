<?php

namespace MagicFramework\Core;

use Exception;

class ApiException extends Exception
{
    // validation problems
    const INTERNAL_ERROR = 'internalError';
    const ERROR_MALFORMED_PAYLOAD = 'malformedPayload';
    const ERROR_VALIDATION_PROBLEMS = 'validationProblems';
    const ERROR_MISSING_TOKEN = 'missingToken';
    const ERROR_MISSING_USER = 'missingUser';
    const ERROR_MISSING_SCHEMA = 'missingSchema';
    const ERROR_MISSING_SCREEN = 'missingScreen';
    const ERROR_MISSING_CONTENT = 'missingContent';
    const ERROR_MISSING_SUB_SCHEMA = 'missingSubSchema';
    const ERROR_MISSING_CUSTOMER = 'missingCustomer';
    const ERROR_MISSING_COMPANY = 'missingCompany';
    const ERROR_MISSING_USER_ROLE = 'missingUserRole';
    const ERROR_MISSING_TQL = 'missingTql';
    const ERROR_SCRIPT_ERROR = 'scriptError';

    const ERROR_FAILED_LOGIN = 'failedLogin';
    const ERROR_EMAIL_EXISTS = 'emailExists';
    const ERROR_NOT_ENOUGH_RIGHTS = 'notEnoughRights';

    const MISSING_ERROR_CODES = [
        self::ERROR_MISSING_TOKEN,
        self::ERROR_MISSING_USER,
        self::ERROR_MISSING_SCHEMA,
        self::ERROR_MISSING_CUSTOMER,
        self::ERROR_MISSING_COMPANY,
        self::ERROR_MISSING_USER_ROLE,
        self::ERROR_MISSING_TQL,
        self::ERROR_MISSING_CONTENT,
        self::ERROR_MISSING_SCREEN,
    ];

    protected array $errorDetails;
    protected array $validationErrors;
    protected int $statusCode;
    protected ?Exception $exception = null;

    public function __construct(string $errorCode, array $errorDetails = [], array $validationErrors = [])
    {
        parent::__construct();
        $this->code = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->validationErrors = $validationErrors;
        $this->statusCode = $this->getStatusCodeByErrorCode($errorCode);
    }

    public function setException(Exception $e) {
        $this->exception = $e;
    }

    protected function getStatusCodeByErrorCode(string $errorCode): string
    {
        $statusCode = 400;
        if (in_array($errorCode, self::MISSING_ERROR_CODES)) {
            $statusCode = 404;
        }

        if ($errorCode == self::ERROR_NOT_ENOUGH_RIGHTS) {
            $statusCode = 403;
        }

        if ($errorCode === self::INTERNAL_ERROR) {
            $statusCode = 500;
        }

        return $statusCode;
    }

    public function generateJson()
    {
        $error = [
            "error" => $this->code
        ];

        if (count($this->errorDetails) > 0) {
            $error['details'] = $this->errorDetails;
        }

        if (count($this->validationErrors) > 0) {
            $list = [];
            /** @var ValidationError $validationError */
            foreach ($this->validationErrors as $validationError) {
                $data = $validationError->generateArray();
                $list[] = $data;
            }

            $error['validationErrors'] = $list;
        }

        if ($this->exception !== null && constant('ENVIRONMENT') === 'dev') {
            $error['exception'] = [
                'code' => $this->exception->getCode(),
                'message' => $this->exception->getMessage(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
                'trace' => $this->exception->getTrace()
            ];
        }

        return json_encode($error, JSON_PRETTY_PRINT);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
