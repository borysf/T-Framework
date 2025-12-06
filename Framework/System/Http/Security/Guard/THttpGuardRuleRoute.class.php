<?php
namespace System\Http\Security\Guard;

use System\Http\Request\THttpRequest;

class THttpGuardRuleRoute extends THttpGuardRule {
    public readonly string $pattern;

    public function __construct($pattern) {
        $this->pattern = $pattern;
    }

    public function verify(THttpRequest $request) : bool {
        if (preg_match('{'.$this->pattern.'}', $request->target->name)) {
            return $this->allowed();
        }

        return true;
    }
}