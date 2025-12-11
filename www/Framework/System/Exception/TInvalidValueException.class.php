<?php
namespace System\Exception;

use System\TException;

class TInvalidValueException extends TException {
    public function __construct(string $name, ?string $expected = null, ?string $reason = null) {
        parent::__construct('Invalid value: '.$name.($expected ? ', expected: '.$expected : ''), reason: $reason);
    }
}