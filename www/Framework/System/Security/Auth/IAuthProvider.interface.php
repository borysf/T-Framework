<?php
namespace System\Security\Auth;

use System\Http\Request\THttpRequest;

interface IAuthProvider {
    public function authenticate(TAuthCredentials $credentials, THttpRequest $request) : ?TAuthUser;
    public function signOut() : void;
}