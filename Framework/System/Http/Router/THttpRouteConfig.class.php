<?php
namespace System\Http\Router;

use System\Http\THttpCode;

/**
 * Route configuration. This class is used to define a new route via `THttpRouter::route` method.
 */
class THttpRouteConfig {
    public readonly THttpRouteTarget $target;
    public readonly string $name;
    public readonly string $pattern;
    public readonly array $matchers;
    public readonly array $defaults;
    public readonly bool $sessionEnabled;
    private readonly THttpRouter $router;
    private mixed $onTargetSet;
    
    public function __construct(
        THttpRouter $router, 
        string $name, 
        string $pattern, 
        array $matchers,
        array $defaults,
        callable $onTargetSet
    ) {
        if (!preg_match('{^[a-z]+(-?([a-z]+:)?[a-z]+)*$}i', $name)) {
            throw new THttpRouterException('Route `'.$name.'`: invalid name format');
        }

        $this->router = $router;
        $this->pattern = $pattern;
        $this->matchers = $matchers;
        $this->name = $name;
        $this->defaults = $defaults;
        $this->onTargetSet = $onTargetSet;
    }

    public function disableSession() : THttpRouteConfig {
        $this->sessionEnabled = false;
        
        return $this;
    }
    
    /** Sets given class and action name as the route target. */
    public function target(string $className, string $actionName = null) : void {
        if (isset($this->target)) {
            throw new THttpRouterException('Route '.$this->name.': ambiguous route: target already set');
        }

        $this->target = new THttpRouteTargetClass(
            $this->router, 
            $this->name,
            $className, 
            $actionName
        );

        if (!isset($this->sessionEnabled)) {
            $this->sessionEnabled = true;
        }

        ($this->onTargetSet)($this->target);
    }

    /** 
     * Sets HTTP redirection as the route target based on route name, its arguments and query params. 
     * Uses 301 Moved Permanently as default response status code.
     */
    public function redirect(string $routeName, array $args = [], $queryParams = [], int $code = THttpCode::MOVED_PERMANENTLY) : void {
        if (isset($this->target)) {
            throw new THttpRouterException('Route '.$this->name.': target already set');
        }

        $this->target = new THttpRouteTargetRedirectHttp(
            $this->name,
            $this->router->generate($routeName, $args, $queryParams), 
            $code
        );
        
        ($this->onTargetSet)($this->target);
    }

    /** 
     * Sets HTTP redirection as the route target based on given URL matching `^https?://` regexp.
     * Uses 301 Moved Permanently as default response status code.
     */
    public function redirectHttp(string $redirect, int $code = THttpCode::MOVED_PERMANENTLY) : void {
        if (isset($this->target)) {
            throw new THttpRouterException('Route '.$this->name.': target already set');
        }

        if (!preg_match('{^(https?://)|/}i', $redirect)) {
            throw new THttpRouterException('Route '.$this->name.': invalid redirect URL');
        }

        $this->target = new THttpRouteTargetRedirectHttp(
            $this->name,
            $redirect, 
            $code
        );
        
        ($this->onTargetSet)($this->target);
    }

    /** 
     * Sets a callback as the routing target. In normal cases, the callback receives only array of route arguments. In case of
     * being defined as a `THttpErrorHandler` target, it receives a `THttpError` instance as the second argument.
     */
    public function callback(callable $callback) : void {
        if (isset($this->target)) {
            throw new THttpRouterException('Route '.$this->name.': ambiguous route: target already set');
        }

        $this->target = new THttpRouteTargetCallback($this->name, $callback);

        ($this->onTargetSet)($this->target);
    }
}