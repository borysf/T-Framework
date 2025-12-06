<?php
namespace System\Http\UriMatcher;

use System\Http\Request\THttpRequest;

class THttpUriMatcherRule {
    public readonly mixed $callback;
    
    private readonly string $_hostRegexp;
    private readonly string|int $_portRegexpOrNumber;
    private readonly string $_pathRegexp;
    private readonly THttpRequest $_request;
    private readonly bool $_https;

    public function __construct(string $hostRegexp, THttpRequest $request) {
        $this->_request = $request;
        $this->_hostRegexp = $hostRegexp;
    }

    public function https(bool $https): THttpUriMatcherRule {
        $this->_https = $https;

        return $this;
    }

    public function path(string $pathRegexp) : THttpUriMatcherRule {
        $this->_pathRegexp = $pathRegexp;

        return $this;
    }

    public function port(string|int $portRegexpOrNumber): THttpUriMatcherRule {
        $this->_portRegexpOrNumber = $portRegexpOrNumber;

        return $this;
    }

    public function onMatch(callable $callback) : void {
        $this->callback = $callback;
    }

    public function runCallback() {
        $cb = $this->callback;

        $cb($this->_request);
    }

    public function match(THttpRequest $request) : bool {
        $matchHost = fn() => preg_match('{'.$this->_hostRegexp.'}i', $request->hostname());
        $matchPort = fn() => !isset($this->_portRegexpOrNumber) || $this->_portRegexpOrNumber == 0 || (
            (is_int($this->_portRegexpOrNumber) && $this->_portRegexpOrNumber == $request->port()) ||
            preg_match('{'.$this->_portRegexpOrNumber.'}i', $request->port())
        );
        $matchPath = fn() => !isset($this->_pathRegexp) || preg_match('{'.$this->_pathRegexp.'}', $request->path());
        $matchProtocol = fn() => !isset($this->_https) || (!$this->_https && !$request->https()) || ($this->_https && $request->https());

        if (isset($this->_hostRegexp)) {
            return $matchProtocol() && $matchPort() && $matchHost() && $matchPath();
        }

        return false;
    }
}
