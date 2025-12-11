<?php
namespace System\Component;

use System\TException;
use Throwable;

class TComponentException extends TException {
    public function __construct(TComponent $component, string $message, ?int $code = 0, ?Throwable $previous = null, ?string $reason = null) {
        parent::__construct($component::class.': '.$message, $code, $previous, $reason);
    }
}