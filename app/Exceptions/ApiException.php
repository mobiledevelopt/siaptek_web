<?php

namespace App\Exceptions;

use Exception;

class ApiException extends \Exception
{
    protected $errorCode;
    protected $status;

    public function __construct($message, $status = 422, $errorCode = 'GENERAL_ERROR')
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errorCode = $errorCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->status);
    }
}