<?php
namespace System\Web\Page\Control\State;

use System\Web\Page\Control\TControl;

class TControlState {
    public function __construct(TControl $control, array $state = []) {
        $this->restore($state);
    }

    public function dump() : array {
        return get_object_vars($this);
    }

    public function restore(array $dump) : void {
        foreach ($dump as $k => $v) {
            $this->$k = $v;
        }
    }
}