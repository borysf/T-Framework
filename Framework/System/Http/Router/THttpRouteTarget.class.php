<?php

namespace System\Http\Router;

/** 
 * Base class for route targets
 */
abstract class THttpRouteTarget implements IHttpRouteTarget
{
    public readonly array $args;
    public readonly string $name;
    public readonly string $className;
    public readonly ?string $actionName;

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function exists(): bool
    {
        return true;
    }

    public function getActionAndMethod(): ?array
    {
        return null;
    }
}
