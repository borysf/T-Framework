<?php

namespace System\Security\Auth;

/**
 * Base class for the user. Contains basic informations
 * such as user's `uid`, his `roles` in the system and any other `data`.
 */
class TAuthUser
{
    const AUTHENTICATED = 'authenticated';

    public readonly int|string|null $uid;
    public readonly array $roles;
    public mixed $public;
    public mixed $secrets;

    public function __construct(int|string|null $uid = null, array $roles = [], mixed $public = null, mixed $secrets = null)
    {
        if ($uid !== null && !in_array(self::AUTHENTICATED, $roles)) {
            $roles[] = self::AUTHENTICATED;
        }

        $this->uid = $uid;
        $this->roles = $roles;
        $this->public = $public;
        $this->secrets = $secrets;
    }

    /**
     * Checks whether the user is authenticated. Authenticated user must have its `uid` set and
     * one must have at least `authenticated` role assigned.
     */
    public function isAuthenticated(): bool
    {
        return $this->uid !== null && in_array(self::AUTHENTICATED, $this->roles);
    }

    /**
     * Checks whether the user has specified role.
     */
    public function hasRole(string $role)
    {
        return in_array($role, $this->roles);
    }
}
