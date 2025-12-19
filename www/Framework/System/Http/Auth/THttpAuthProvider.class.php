<?php
namespace System\Http\Auth;

use System\Http\Request\THttpRequest;
use System\Security\Auth\IAuthProvider;
use System\Security\Auth\TAuthCredentials;
use System\Security\Auth\TAuthUser;

class THttpAuthProvider implements IAuthProvider {
    public function authenticate(TAuthCredentials $credentials, THttpRequest $request) : ?TAuthUser {
        return null;
    }

    public function signOut() : void {
        // noop
    }
}