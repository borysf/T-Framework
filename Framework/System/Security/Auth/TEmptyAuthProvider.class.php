<?php
namespace System\Security\Auth;

use System\Http\Request\THttpRequest;

/** 
 * Empty authentication provider. This is a dummy class and in order to
 * create your own authentication process, you must write your own implementation.
 */
class TEmptyAuthProvider implements IAuthProvider {
    public function authenticate(TAuthCredentials $credentials, THttpRequest $request): ?TAuthUser {
        return null;
    }

    public function signOut() : void {}
}