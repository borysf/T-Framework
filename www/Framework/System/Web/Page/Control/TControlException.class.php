<?php
namespace System\Web\Page\Control;

use System\Component\TComponentException;
use Throwable;

class TControlException extends TComponentException {
    public function __construct(TControl $control, string $message, ?int $code = 0, ?Throwable $previous = null, ?string $reason = null) {
        parent::__construct($control, $message, $code, $previous, $reason);
    }
}