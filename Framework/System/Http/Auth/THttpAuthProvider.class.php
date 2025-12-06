<?php
namespace System\Http\Auth;

use System\Security\Auth\IAuthProvider;
use System\Security\Auth\TAuthCredentials;
use System\Security\Auth\TAuthUser;

class THttpAuthProvider implements IAuthProvider {
    public function authenticate(TAuthCredentials $credentials) : ?TAuthUser {
        return null;
    }
}