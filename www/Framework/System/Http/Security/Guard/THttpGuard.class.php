<?php

namespace System\Http\Security\Guard;

use System\Debug\TDebug;
use System\Http\Error\THttpError;
use System\Http\Request\THttpRequest;
use System\Http\Session\THttpSession;
use System\Http\THttpCode;
use System\Security\Auth\TAuth;

/**
 * Security class protecting restricted routes and managing CSRF
 * protection. Its role is to check whether the current user has
 * sufficient rights to access given route, otherwise throws 
 * `THttpError(401)` (unauthorized) HTTP error.
 * 
 * On the other hand, it is responsible for CSRF tokens management
 * and validation. If CSRF protection is enabled, then any `TForm`
 * control within the app is validated against CSRF token correctness.
 */
class THttpGuard
{
    private array $__rules = [];
    private readonly bool $__csrfProtectionEnabled;
    private readonly THttpSession $__session;

    public function __construct(THttpSession $session, TAuth $auth)
    {
        $this->__session = $session;
    }

    public function path(string $path): THttpGuardRulePath
    {
        $rule = new THttpGuardRulePath($path);

        $this->__rules[] = $rule;

        return $rule;
    }

    public function route(string $routeName): THttpGuardRuleRoute
    {
        $rule = new THttpGuardRuleRoute($routeName);

        $this->__rules[] = $rule;

        return $rule;
    }

    public function enableCsrfProtection(): void
    {
        TDebug::info('CSRF protection enabled');
        $this->__csrfProtectionEnabled = true;
    }

    public function getCsrfToken(): ?string
    {
        if (!$this->isCsrfProtectionEnabled()) {
            return null;
        }

        $tokens = $this->__session->read('system:csrf-tokens', []);

        if (empty($tokens)) {
            $tokens = $this->__generateCsrfTokens(5);
        }

        $this->__session->write('system:csrf-tokens', $tokens);

        return $tokens[array_rand($tokens)];
    }

    public function isCsrfProtectionEnabled(): bool
    {
        return isset($this->__csrfProtectionEnabled) && $this->__csrfProtectionEnabled;
    }

    public function authorizeRequest(THttpRequest $request): void
    {
        foreach ($this->__rules as $rule) {
            if (!$rule->verify($request)) {
                TDebug::log('Rule', $rule, 'blocked access to requested page');
                throw new THttpError(THttpCode::UNAUTHORIZED, 'access blocked by guard rule');
            }
        }

        $action = $request->target->getActionAndMethod();
        $validateCsrf = !$action || $action[0]->validateCsrf;

        if ($this->isCsrfProtectionEnabled() && $request->method() == 'POST' && $validateCsrf) {
            $receivedToken = isset($request->post->__CSRF) ? $request->post->__CSRF : '';

            if (!$this->validateCsrfToken($receivedToken)) {
                TDebug::error('CSRF token validation failed');
                throw new THttpError(THttpCode::BAD_REQUEST, 'CSRF token validation failed');
            }
        }
    }


    public function validateCsrfToken(string $token, bool $remove = true): bool
    {
        $tokens = $this->__session->read('system:csrf-tokens', []);

        TDebug::log('validting', $token, $tokens);

        if (in_array($token, $tokens)) {
            if ($remove) {
                $this->__session->write('system:csrf-tokens', array_filter($tokens, fn ($t) => $t != $token));
            }

            return true;
        }

        return false;
    }

    private function __generateCsrfTokens(int $numberOfTokens): array
    {
        $tokens = [];

        for ($i = 0; $i < $numberOfTokens; $i++) {
            $tokens[] = bin2hex(random_bytes(64));
        }

        return $tokens;
    }
}
