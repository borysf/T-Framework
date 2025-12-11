<?php
namespace System\Security\Auth;

use System\Http\Request\THttpRequest;
use System\Http\Response\THttpResponse;
use System\Http\Session\THttpSession;

/**
 * Security class responsible for authenticating users.
 * Provides a common interface for you to create any kind of authenticators 
 * via configurable auth providers implementing `IAuthProvider` interface.
 */
class TAuth {
    private readonly IAuthProvider $__provider;
    private readonly THttpSession $__session;
    private readonly THttpRequest $__request;
    private readonly THttpResponse $__response;

    public function __construct(THttpSession $session, THttpRequest $request, THttpResponse $response, IAuthProvider $provider) {
        $this->__session = $session;
        $this->__request = $request;
        $this->__response = $response;
        $this->__provider = $provider;
    }

    /**
     * Authenticates user based on provided `TAuthCredentials` credentials.
     * Credentials can be filled with any data required for authentication
     * process that your own authentication provider understands.
     */
    public function authenticate(TAuthCredentials $credentials) : bool {
        $user = $this->__provider->authenticate($credentials, $this->__request);

        if ($user) {
            $this->__session->write('system:user', $user);

            return true;
        }

        return false;
    }

    /**
     * Checks whether current user is authenticated.
     */
    public function isAuthenticated() : bool {
        if (!$this->__session->isStarted()) {
            return false;
        }

        return $this->currentUser()->isAuthenticated();
    }

    /**
     * Returns current user.
     * Please note that unless a session is started, the returned
     * user is a single session-independent object, so any changes
     * made to this object are lost during following requests.
     */
    public function currentUser() : TAuthUser {
        static $_user = new TAuthUser;

        if (!$this->__session->isStarted()) {
            return $_user;
        }

        return $this->__session->read('system:user', $_user);
    }

    /**
     * Signs out the user. First calls `signOut` method of the
     * authentication provider then destroys the session.
     */
    public function signOut() : void {
        $this->__provider->signOut();
        $this->__session->destroy();
    }
}