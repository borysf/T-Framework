<?php

namespace System\Http\Router;

use System\Debug\TDebug;
use System\Http\Error\THttpError;
use System\Http\Request\THttpRequest;
use System\Http\Response\THttpResponse;
use System\Http\Session\THttpSession;
use System\Http\THttpCode;
use System\Web\Service\System\TMediaService;

/**
 * HTTP router. The router have few responsibilities. First, it it responsible for reading the request and
 * matching it with defined routes. When a request is matched then the route target is executed, otherwise
 * 404 Not Found is raised. The other responsibility is to generate URLs based on route name and its arguments.
 *
 * Only one instance is running in the application.
 */
class THttpRouter
{
    private array $__routes = [];
    private array $__configs = [];

    private readonly string $__rootUriPath;

    /** Current HTTP request. */
    public readonly THttpResponse $response;

    /** Current HTTP response. */
    public readonly THttpRequest $request;

    private readonly THttpSession $__session;

    /**
     * Whether to ignore trailing slashes in URI path while matching routes.
     * When set to `true`, then paths `/foo/bar`, `/foo/bar/`, `/foo/bar//` and so on
     * will be considered equal. This does not apply when path ends with file name
     * with extension.
     */
    public bool $ignoreTrailingSlash = false;

    /**
     * Whether to append trailing slash to generated URIs. This does not apply when
     * URI path ends with file name with extension.
     */
    public bool $appendTrailingSlash = false;

    /**
     * Whether to prepend hostname to generated URIs.
     */
    public bool $prependHostName = false;

    /**
     * Constructs the router under given $rootUriPath.
     */
    public function __construct(string $rootUriPath, THttpRequest $request, THttpResponse $response, THttpSession $session)
    {
        $this->__rootUriPath = trim($rootUriPath, '/');
        $this->response = $response;
        $this->request = $request;
        $this->__session = $session;

        $this->route('system:assets:images', '/images/{file}', [
            'file' => '.+'
        ])->disableSession()->target(TMediaService::class, 'sendImage');

        $this->route('system:assets:styles', '/styles/{file}', [
            'file' => '.+'
        ])->disableSession()->target(TMediaService::class, 'sendStyle');

        $this->route('system:assets:statics', '/static/{file}', [
            'file' => '.+'
        ])->disableSession()->target(TMediaService::class, 'sendStatic');

        $this->route('system:assets:scripts', '/scripts/{file}', [
            'file' => '.+'
        ])->disableSession()->target(TMediaService::class, 'sendScript');

        $this->route('system:assets:bundle', '/@{bundleId}/{file}', [
            'bundleId' => '[a-z0-9]+',
            'file' => '.+'
        ])->disableSession()->target(TMediaService::class, 'sendAssetFromBundle');
    }

    /**
     * Creates new route configuration with its name and URI pattern. If URI pattern defines
     * arguments (`{...}`), then matchers array is required for these arguments to parse.
     * Matchers are defined as associative array where key is argument name and value is regular
     * expression.
     */
    public function route(string $name, string $pattern, array $matchers = [], array $defaults = []): THttpRouteConfig
    {
        if (isset($this->__configs[$name])) {
            throw new THttpRouterException('Route `' . $name . '` already defined');
        }

        $config = new THttpRouteConfig(
            $this,
            $name,
            '/' . ($this->__rootUriPath ? $this->__rootUriPath . '/' : '') . ltrim($pattern, '/'),
            $matchers,
            $defaults,
            fn (THttpRouteTarget $target) => $this->__routes[$name] = $target
        );

        $this->__configs[$name] = $config;

        return $config;
    }

    /**
     * Redirects the request to another location using HTTP `Location` header. By default, response
     * code is set to 307 Temporary Redirect. As the $target argument accepts array of route definintion
     * (`['route_name', [...arguments], [...query_params]]`), or URL annotation string (`[route_name [...route_param=value]]`),
     * or URL matching regexp `^https?://`.
     */
    public function redirect(string|array $target, array $args = [], int $code = THttpCode::TEMPORARY_REDIRECT): void
    {
        if (is_string($target)) {
            if (preg_match('{^\[.*\]$}', $target)) {
                $url = $this->generateFromString($target);
            } else if (preg_match('{^(https?://)|/}', $target)) {
                $url = $target;
            } else {
                $url = $this->generate($target, $args);
            }
        } else {
            $url = $this->generate(...$target);
        }

        $this->response->setCode($code);
        $this->response->setHeader('Location', $url);
        $this->response->send('');
    }

    /**
     * Returns route configuration by its name.
     */
    public function getRouteConfig(string $name): THttpRouteConfig
    {
        if (!isset($this->__configs[$name])) {
            throw new THttpRouterException('Route `' . $name . '` does not exist');
        }

        return $this->__configs[$name];
    }

    /** Handles request. Used internally by the framework. */
    public function handleRequest(): void
    {
        foreach ($this->__configs as $config) {
            if ($target = $this->__match($config)) {
                TDebug::log('Route matched', $config);

                if ($config->sessionEnabled) {
                    $this->__session->startIfCookiePresent();
                    $this->request->__fillSessionData();
                }

                $this->request->setTarget($target);
                $this->request->setArgs($target->args);

                return;
            }
        }

        TDebug::error('No matching route found', '=>', THttpCode::NOT_FOUND);
        throw new THttpError(THttpCode::NOT_FOUND, 'No matching route found');
    }

    /** Generates URL from URL annotation string (`[route_name [...route_param=value]]`) */
    public function generateFromString(string $url): ?string
    {
        if (preg_match('{^\s*\[(?P<route>[a-z]+(-?([a-z]+:)?[a-z]+)*)(?P<params>\s+.+)?\s*]$}', $url, $matches)) {
            $config = $this->getRouteConfig($matches['route']);

            $args = [];
            $params = [];

            if (isset($matches['params'])) {
                parse_str(preg_replace('{\s+}', '&', $matches['params']), $params);

                foreach ($params as $name => $value) {
                    if (isset($config->matchers[$name])) {
                        $args[$name] = $value;
                        unset($params[$name]);
                    }
                }
            }

            return $this->generate($matches['route'], $args, $params);
        }

        throw new THttpRouterException('could not parse url annotation string: `' . $url . '`');
    }

    /** Generates URL based on given route name, array of arguments and array of query params */
    public function generate(string $name, array $args = [], array $queryParams = []): string
    {
        $config = $this->getRouteConfig($name);

        $url = preg_replace_callback('@{(?P<var>[a-zA-Z0-9_]+)}@', function ($match) use ($args, $config) {
            $argName = $match['var'];

            if (!array_key_exists($argName, $args)) {
                if (!array_key_exists($argName, $config->defaults)) {
                    throw new THttpRouterException('Route ' . $config->name . ': missing route arg `' . $argName . '`');
                } else {
                    $argValue = $config->defaults[$argName];
                }
            } else {
                $argValue = $args[$argName];
            }

            $regexp = '^' . $config->matchers[$argName] . '$';

            if (!preg_match('{' . $regexp . '}', $argValue)) {
                throw new THttpRouterException('Route ' . $config->name . ': route arg `' . $argName . '` (' . $argValue . ') does not match ' . $regexp);
            }

            return $argValue;
        }, $config->pattern);

        if ($this->appendTrailingSlash) {
            $url = preg_replace('{/[^\./]+$}', '\\0/', $url);
        }

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $this->prependHostName
            ? ($this->request->https() ? 'https' : 'http') . '://' . $this->request->hostname() . $url
            : $url;
    }


    private function __match(THttpRouteConfig $config): ?THttpRouteTarget
    {
        $path = $this->request->path();
        $pattern = $config->pattern;

        if ($this->ignoreTrailingSlash && preg_match('{/[^\.]+$}', $path)) {
            $path = rtrim($path, '/');
            $pattern = rtrim($pattern, '/');
        }

        if ($path == $pattern) {
            if (!isset($config->target)) {
                throw new THttpRouterException('Route found (' . $config->name . ') but has no target defined');
            }
            $config->target->setArgs([]);

            return $config->target;
        }

        if (preg_match_all('@{(?P<var>[a-zA-Z0-9_]+)}@', $pattern, $vars)) {
            $regexp = $this->__createRegexp($vars['var'], $config->matchers, $pattern);

            if (preg_match($regexp, $path, $matches)) {
                $args = [];
                foreach ($vars['var'] as $var) {
                    $args[$var] = $matches[$var];
                }

                $config->target->setArgs($args);

                return $config->target;
            }
        }

        return null;
    }

    private function __createRegexp(array $vars, array $matchers, string $pattern): string
    {
        $url = str_replace('(', '\(', $pattern);
        $url = str_replace(')', '\)', $pattern);
        $url = str_replace('.', '\.', $pattern);

        foreach ($vars as $name) {
            if (!isset($matchers[$name])) {
                throw new THttpRouterException('Undefined matcher for variable `' . $name . '` in route `' . $pattern . '`'); //@todo exception
            }

            $format = $matchers[$name];

            $url = str_replace('{' . $name . '}', "(?P<$name>$format)", $url);
        }

        return '{^' . $url . '$}';
    }
}
