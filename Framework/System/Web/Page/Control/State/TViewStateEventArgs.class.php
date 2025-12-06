<?php
namespace System\Web\Page\Control\State;

use System\Web\Page\Control\Event\TEventArgs;

class TViewStateEventArgs extends TEventArgs {
    public function __construct(array $state) {
        foreach ($state as $k => $v) {
            $this->$k = $v;
        }
    }
}