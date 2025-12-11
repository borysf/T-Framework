<?php
namespace System\Http\Error;

use System\Http\THttpCode;
use System\TException;

/**
 * Exception class representing HTTP error.
 * Your app should throw instances of `THttpError` whenever it is expected 
 * for your app to finish with other than `200 OK` response code.
 * 
 * `THttpError` exceptions are handled in a special manner by the framework.
 * They can be nicely handled by previously configured `THttpErrorHandler`.
 * 
 * For example, whenever your app throws `THttpError(404)`, the exception
 * can be handled and user can get a nice `404` page instead of empty response
 * with `404 Not Found` code.
 */
class THttpError extends TException {
    public function __construct(int $code, ?string $reason = null) {
        parent::__construct(new THttpCode($code), $code, reason: $reason);
    }
}