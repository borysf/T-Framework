<?php
namespace System\Web\Action;

use Attribute;
use System\Http\Request\THttpRequest;

#[Attribute(Attribute::TARGET_METHOD)]
class Action {
    public readonly string $name;
    public readonly ?array $methods;
    public readonly ?string $cacheControl;
    public readonly string $contentType;
    public readonly bool $validateCsrf;
    public readonly bool $runOnPostBack;

    public function __construct(
        string $name, 
        string|array|null $method = null, 
        string $contentType = 'text/html; charset='.T_DEFAULT_CHARSET, 
        bool $validateCsrf = true,
        bool $runOnPostBack = true,
        ?string $cacheControl = null
    ) {
        $this->name = $name;
        $this->methods = is_string($method) ? [$method] : $method;
        $this->contentType = $contentType;
        $this->validateCsrf = $validateCsrf;
        $this->runOnPostBack = $runOnPostBack;
        $this->cacheControl = $cacheControl;
    }

    public function matchRequest(THttpRequest $request) : bool {
        return empty($this->methods) || in_array($request->method(), $this->methods);
    }
}