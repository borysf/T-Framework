<?php

namespace System\Web;

use System\Debug\TDebug;
use System\Web\Action\TActionArgs;
use System\Http\Router\THttpRouteTarget;
use System\TApplication;

/**
 * Base runner class responsible for running pages and services.
 */
abstract class TRunner
{
    protected THttpRouteTarget $target;
    protected TApplication $application;

    public function __construct(THttpRouteTarget $target, TApplication $application)
    {
        $this->target = $target;
        $this->application = $application;
    }

    /**
     * Runs requested page or service based on current route.
     * If route points to an action, looks for the action in the page or service
     * class and runs it as well. Static action methods are called statically
     * WITHOUT instantiating base class, thus Page/Service_Init and Page/Service_Load
     * methods are not called.
     */
    public function run(): string
    {
        $className = $this->target->className;
        $invokeMethod = null;
        $action = null;
        $args = new TActionArgs(isset($this->target->args) ? $this->target->args : []);

        if ($actionAndMethod = $this->target->getActionAndMethod()) {
            [$action, $invokeMethod] = $actionAndMethod;

            $this->application->response->setHeader('Content-Type', $action->contentType);

            if ($action->cacheControl) {
                $this->application->response->setHeader('Cache-Control', $action->cacheControl);
            }

            if (($parameters = $invokeMethod->getParameters()) && !empty($parameters)) {
                $type = (string)$parameters[0]->getType();

                if ($type != TActionArgs::class && !is_subclass_of($type, TActionArgs::class)) {
                    throw new TRuntimeException(
                        'Argument #1 must be compatible with ' . TActionArgs::class,
                        customFileName: $invokeMethod->getFileName(),
                        customLineNo: $invokeMethod->getStartLine()
                    );
                }

                $args = new $type(isset($this->target->args) ? $this->target->args : []);
            }

            if ($invokeMethod->isStatic()) {
                @ob_start();
                TDebug::log($this->target->className, $invokeMethod->getName(), ':', 'handling action', $action, 'with args', $args);
                $invokeMethod->invoke(null, $args, $this->application);
                return @ob_get_clean();
            }
        }

        $instance = new $className($args, $this->application);
        @ob_start();
        $instance->load($args);

        if ($invokeMethod && (!$instance->isPostBack() || $action->runOnPostBack)) {
            TDebug::log($instance::class, $invokeMethod->getName(), ':', 'handling action', $action, 'with args', $args);
            $invokeMethod->invoke($instance, $args);
        }

        $instance->finish();
        return @ob_get_clean();
    }
}
