<?php

namespace System\Http\Router;

use System\TApplication;

/**
 * Defines given URL as the HTTP redirection route target.
 */
class THttpRouteTargetRedirectHttp extends THttpRouteTarget
{
    private string $url;
    private int $code;
    public readonly string $name;

    public function __construct(string $name, string $url, int $code)
    {
        $this->name = $name;
        $this->url = $url;
        $this->code = $code;
    }

    /**
     * Runs this target.
     */
    public function run(TApplication $app): string
    {
        $app->response->setCode($this->code);
        $app->response->setHeader('Location', $this->url);
        $app->response->setContent(null);
        exit;
    }
}
