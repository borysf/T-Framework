<?php
namespace System\Http\Error;

use System\Debug\TDebug;
use System\Http\Router\THttpRouteConfig;
use System\Http\Router\THttpRouter;
use System\Http\Router\THttpRouteTarget;
use System\Http\Router\THttpRouteTargetCallback;
use System\Http\THttpException;

/**
 * Handles `THttpError` exceptions.
 * Whenever your app throws `THttpError` exception it goes
 * to the handler in order to be handled nicely. If HTTP code
 * matches one of the handler's rules, the `THttpRouter` gets
 * redirected to another target.
 */
class THttpErrorHandler {
    private array $__targets = [];
    private THttpRouter $__router;

    public function __construct(THttpRouter $router) {
        $this->__router = $router;
    }

    public function handle(THttpError $error) : ?THttpRouteTarget {
        if (isset($this->__targets[$error->getCode()])) {
            TDebug::log('Handling HTTP error', $error);

            $target = $this->__targets[$error->getCode()];

            if ($target instanceof THttpRouteTargetCallback) {
                $target->setError($error);
            }

            if (!$target) {
                throw new THttpException('Target for error `'.$error->getCode().'` not defined');
            }

            TDebug::log('Retargeting:', $target);

            return $target;
        }

        return null;
    }

    public function on(int $code) : THttpRouteConfig {
        return new THttpRouteConfig(
            $this->__router, 'system:error', '', [], [],
            fn (THttpRouteTarget $target) => $this->__targets[$code] = $target
        );
    }
}
