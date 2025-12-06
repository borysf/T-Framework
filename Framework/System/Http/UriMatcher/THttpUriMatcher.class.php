<?php
namespace System\Http\UriMatcher;

use System\Http\UriMatcher\THttpUriMatcherRule;
use System\Http\Request\THttpRequest;

class THttpUriMatcher {
    public readonly THttpRequest $request;
    private array $_rules = [];

    public function __construct() {
        $this->request = new THttpRequest;
    }

    public function host(string $hostRegexp) : THttpUriMatcherRule {
        $rule = new THttpUriMatcherRule($hostRegexp, $this->request);
        $this->_rules[] = $rule;
        return $rule;
    }

    public function run() : ?THttpUriMatcherRule {
        foreach ($this->_rules as $rule) {
            if ($rule->match($this->request)) {
                ($rule->callback)($this->request);
                return $rule;
            }
        }

        return null;
    }
}