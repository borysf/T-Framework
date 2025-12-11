<?php

namespace System\Http\Router;

use ReflectionClass;
use System\Http\Error\THttpError;
use System\Http\THttpCode;
use System\TApplication;
use System\TApplicationException;
use System\TException;
use System\Web\Action\Action;
use System\Web\Page\TPage;
use System\Web\Page\TPageRunner;
use System\Web\Service\TServiceRunner;

/**
 * Defines given class name and action name as the route target.
 */
class THttpRouteTargetClass extends THttpRouteTarget
{
    public readonly string $className;
    public readonly ?string $actionName;
    public readonly string $name;
    private readonly THttpRouter $router;

    public function __construct(THttpRouter $router, string $name, string $className, ?string $actionName = null)
    {
        $this->className = $className;
        $this->actionName = $actionName;
        $this->router = $router;
        $this->name = $name;
    }

    public function exists(): bool
    {
        return class_exists($this->className);
    }

    /**
     * INTERNAL USE ONLY. Returns matching action and its class method.
     */
    public function getActionAndMethod(): ?array
    {
        if (!$this->actionName) {
            return null;
        }

        $ref = new ReflectionClass($this->className);

        $matching = [];
        $hasMatchingActionByName = false;
        $attribute = null;

        foreach ($ref->getMethods() as $method) {
            $attributes = $method->getAttributes(Action::class);

            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();

            if ($attribute->name == $this->actionName) {
                $hasMatchingActionByName = true;

                if (!$method->isPublic()) {
                    throw new TException(
                        $this->className . ': Action `' . $this->actionName . '`: handler method (' . $method->getName() . ') must be public',
                        customLineNo: $method->getStartLine(),
                        customFileName: $method->getFileName()
                    );
                }

                if ($attribute->matchRequest($this->router->request)) {
                    $matching[] = [$attribute, $method];
                }
            }
        }

        if (!$hasMatchingActionByName) {
            throw new THttpError(THttpCode::NOT_FOUND, 'No action `' . $this->actionName . '` found');
        } else if (empty($matching)) {
            throw new THttpError(THttpCode::METHOD_NOT_ALLOWED, 'There is no action `' . $this->actionName . '` matching request method: ' . $this->router->request->method());
        } else if (count($matching) > 1) {
            throw new THttpError(THttpCode::INTERNAL_SERVER_ERROR, 'Ambiguous action. There are ' . count($matching) . ' actions `' . $this->actionName . '` matching request method: ' . $this->router->request->method());
        }

        return $matching[0];
    }

    /**
     * Runs this target.
     */
    public function run(TApplication $app): string
    {
        if (!$this->exists()) {
            throw new TApplicationException('Fatal: ' . $this->className . ': class not found');
        }

        $pageRootClass = \System\Web\Page\TPage::class;
        $serviceRootClass = \System\Web\Service\TService::class;

        if (is_subclass_of($this->className, $pageRootClass)) {
            $service = new TPageRunner($this, $app);
        } else if (is_subclass_of($this->className, $serviceRootClass)) {
            $service = new TServiceRunner($this, $app);
        } else {
            throw new TApplicationException('Fatal: ' . $this->className . ': must be a subclass of `' . $pageRootClass . '` or `' . $serviceRootClass . '`');
        }

        return $service->run();
    }
}
