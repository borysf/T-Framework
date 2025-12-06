<?php
namespace System\Http\Router;

use System\Http\Error\THttpError;
use System\TApplication;

/**
 * Class representing route target as a defined callback.
 * When `THttpRouter` matches the request with this target,
 * the assigned callback will be called.
 */
class THttpRouteTargetCallback extends THttpRouteTarget {
    public readonly string $name;
    public readonly mixed $callback;
    private readonly THttpError $error;

    public function __construct(string $name, callable $callback) {
        $this->name = $name;
        $this->callback = $callback;
    }

    /** 
     * If the target was created by `THttpErrorHandler`, 
     * the causing `THttpError` instance is set to be available
     * in the callback.
     */
    public function setError(THttpError $error) : void {
        $this->error = $error;
    }

    public function run(TApplication $app) : string {
        return ($this->callback)(isset($this->args) ? $this->args : [], isset ($this->error) ? $this->error : null);
    }
}