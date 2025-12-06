<?php
namespace System\Http\Security\Guard;

abstract class THttpGuardRule implements IHttpGuardRule {
    public readonly mixed $allowed;

    public function allow(bool|callable $allow) : void {
        $this->allowed = $allow;
    }

    public function allowed() : bool {
        if (is_bool($this->allowed)) {
            return $this->allowed;
        }
        
        if (is_callable($this->allowed)) {
            return ($this->allowed)();
        }

        return false;
    }
}