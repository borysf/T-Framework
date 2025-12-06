<?php
namespace System\Http\Security\Guard;

use System\Http\Request\THttpRequest;

interface IHttpGuardRule {
    public function allow(bool|callable $allow) : void;
    public function verify(THttpRequest $request) : bool;
}