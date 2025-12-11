<?php
namespace System\Web\Page\Control\Template\Compiler;

use System\TException;

class TTemplateCompilerException extends TException {
    public function __construct(string $fileName, int $lineNo, string $message, ?string $reason = null) {
        parent::__construct($message, reason: $reason, customFileName: $fileName, customLineNo: $lineNo);
    }
}