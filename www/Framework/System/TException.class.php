<?php
namespace System;

use Exception;
use Throwable;

/**
 * Base exception class. Adds string $reason property
 */
class TException extends Exception {
    public readonly ?string $reason;
    public readonly ?string $customFileName;
    public readonly ?int $customLineNo;

    public function __construct(string $message = '', int $code = 0, Throwable $previous = null, ?string $reason = null, ?string $customFileName = null, ?int $customLineNo = null) {
        $this->reason = $reason;
        $this->customFileName = $customFileName;
        $this->customLineNo = $customLineNo;
        parent::__construct($message, $code, $previous);    
    }
}