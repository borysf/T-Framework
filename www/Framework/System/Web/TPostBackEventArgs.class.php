<?php
namespace System\Web;

use System\Web\Page\Control\Event\TEventArgs;

class TPostBackEventArgs extends TEventArgs {
    public readonly ?array $value;

    public function __construct(?array $value) {
        $this->value = $value;
    }
}