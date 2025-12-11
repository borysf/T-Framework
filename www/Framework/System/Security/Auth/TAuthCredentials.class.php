<?php

namespace System\Security\Auth;

/**
 * Use this class to pass authentication credentials to underlying auth
 * provider. You can fill the instance with any data your provider 
 * understands and knows how to handle.
 */
class TAuthCredentials
{
    public mixed $data;
}
