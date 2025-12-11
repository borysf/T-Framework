<?php

namespace System;

use ReflectionClass;
use System\Debug\TDebug;
use System\Debug\TErrorHandler;
use System\Debug\TExceptionDisplay;

use System\Http\Request\THttpRequest;
use System\Http\Response\THttpResponse;
use System\Http\Router\THttpRouter;
use System\Http\Error\THttpErrorHandler;
use System\Http\Error\THttpError;
use System\Http\Security\Guard\THttpGuard;
use System\Http\Session\IHttpSessionProvider;
use System\Http\Session\THttpSession;
use System\Http\Session\THttpSessionProvider;
use System\Http\THttpCode;
use System\Security\Auth\IAuthProvider;
use System\Security\Auth\TAuth;
use System\Security\Auth\TEmptyAuthProvider;
use System\Web\Page\Control\State\IViewStateProvider;
use System\Web\Page\Control\State\TViewState;
use System\Web\Page\Control\State\TViewStateProvider;
use TAutoloader;
use Throwable;

ini_set('magic_quotes_gpc', 'off');
ini_set('register_globals', 'off');
ini_set('display_errors', 'on');
ini_set('html_errors', 'off');
ini_set('docref_root', '');

/**
 * TApplication class is the base class for every app in `T`. 
 * 
 * This class is designed to be extended by your own implementation to configure behavior of your own app.
 * In your own implementation you can simply configure its basic components such as: HTTP router, HTTP guard,
 * authorization, DB connections and so on.
 */
abstract class TApplication
{
    /** 
     * Application running in development mode is slower than in production. This is because many
     * development features are turned on, such as logging, template control hierarchy checks, 
     * printing out exceptions. Debugging information from TDebug class is printed out to the browser console.
     * Use this mode ONLY for development purposes.
     */
    final public const MODE_DEVELOPMENT = 1;

    /**
     * Production mode disables debugging features. All unhandled exceptions (except THttpError) are turned into HTTP 500 (Interlal Server Error).
     * This is the default so when no mode is provided to the app constructor, MODE_PRODUCTION is assumed.
     */
    final public const MODE_PRODUCTION  = 2;

    private static int $_mode;
    private static string $_rootDir;
    private static string $_rootUriPath;

    /** Configuration parameters passed to application via constructor. Use this configuration parameter to pass anything that you will require in your app. */
    public readonly mixed $config;

    /** Current HTTP request instance. */
    public readonly THttpRequest $request;

    /** Current HTTP response instance. */
    public readonly THttpResponse $response;

    /** HTTP router responsible for routing request URLs. */
    public readonly THttpRouter $router;

    /** HTTP guard responsible for authorizing access to requested URLs. */
    public readonly THttpGuard $guard;

    /** Users authenticator instance. */
    public readonly TAuth $auth;

    /** Unique application ID */
    public readonly string $appId;

    /** HTTP session instance. */
    public readonly THttpSession $session;

    /** State instance */
    public readonly TViewState $state;

    private readonly THttpErrorHandler $httpErrorHandler;

    final public static function getRootUriPath(): string
    {
        return self::$_rootUriPath;
    }

    /** Returns true if app is running in development mode. */
    final public static function isDevelopment(): bool
    {
        return self::$_mode == self::MODE_DEVELOPMENT;
    }

    /** Returns true if app is running in production mode. */
    final public static function isProduction(): bool
    {
        return self::$_mode == self::MODE_PRODUCTION;
    }

    /** Returns root directory where your app class is located. */
    final public static function getRootDir(): string
    {
        return self::$_rootDir;
    }

    /** Creates app instance under given URI path. */
    final public function __construct(string $rootUriPath = '/', int $mode = self::MODE_PRODUCTION, mixed $config = null)
    {
        TDebug::initialize();

        if (!defined('T_DEFAULT_CHARSET')) define('T_DEFAULT_CHARSET', ini_get('default_charset'));

        set_error_handler([new TErrorHandler, 'handle']);
        set_exception_handler(function (Throwable $e) {
            $this->__handleUnhandledException($e);
        });
        register_shutdown_function(function () {
            $this->terminate();

            if (isset($this->response)) {
                $this->processResponse($this->response);

                if (self::isDevelopment()) {
                    TDebug::handleResponse($this->response);
                }

                $this->response->send();
            }
        });

        $file = (new ReflectionClass($this::class))->getFileName();

        $this->config           = $config;

        self::$_mode            = $mode;
        self::$_rootDir         = dirname($file) . DIRECTORY_SEPARATOR;
        self::$_rootUriPath     = $rootUriPath;

        TAutoloader::setProjectDir(self::$_rootDir);

        $this->appId            = substr(sha1($file), 0, 7);
        $this->session          = new THttpSession($this->createHttpSessionProvider(), ['name' => 'ssid_' . $this->appId, 'cookie_path' => $rootUriPath]);
        $this->request          = new THttpRequest($this->session);
        $this->response         = new THttpResponse($this->request);
        $this->router           = new THttpRouter($rootUriPath, $this->request, $this->response, $this->session);
        $this->state            = new TViewState($this->request, $this->createViewStateProvider());
        $this->auth             = new TAuth($this->session, $this->request, $this->response, $this->createAuthProvider());
        $this->guard            = new THttpGuard($this->session, $this->auth);
        $this->httpErrorHandler = new THttpErrorHandler($this->router);

        $this->configureHttpRouter($this->router);
        $this->configureHttpErrorHandler($this->httpErrorHandler);
        $this->configureHttpGuard($this->guard);
        $this->prepare();
    }

    /** Prepare method is called once app internals are configured. You can override this method if you need to perform some other specific configuration taks. */
    protected function prepare(): void
    {
        TDebug::info('To prepare some additional configuration, i.e. DB access and so on, override prepare() method in you application');
    }

    /** Creates HTTP session provider. By default, creates a standard THttpSessionProvider that use built-in PHP sessions. */
    protected function createHttpSessionProvider(): IHttpSessionProvider
    {
        TDebug::info('Using default THttpSessionProvider. To use your own provider, please override createHttpSessionProvider method in your application');
        return new THttpSessionProvider;
    }

    /** 
     * Creates view state provider. By default, creates a standard TViewStateProvider that save all view state data in hidden TForm's field.
     * To make it more secure, override this method to configure data encryption key or to create your own view state provider implementing
     * IViewStateProvider interface.
     */
    protected function createViewStateProvider(): IViewStateProvider
    {
        TDebug::warn('Using default TViewStateProvider without encryption key. To set encryption key or use your own provider, please override createViewStateProvider method in your application');
        return new TViewStateProvider;
    }

    /**
     * Creates authentication provider. By default, creates a TEmptyAuthProvider which does absolutely nothing. To authenticate users in your app,
     * create your own auth provider implementing IAuthProvider interface.
     */
    protected function createAuthProvider(): IAuthProvider
    {
        TDebug::warn('No auth provider created. To authorize users in your app, override createAuthProvider method in your application');
        return new TEmptyAuthProvider;
    }

    /**
     * Configures HTTP guard which authorizes requests. To restrict access to some parts of your app, override this method.
     */
    protected function configureHttpGuard(THttpGuard $guard): void
    {
        TDebug::warn('THttpGuard not configured. This will cause all requests and user permissions to be unverified. To configure, please implement configureHttpGuard(THttpGuard $guard) method.');
    }

    /** 
     * Configures HTTP router. Router must be configured in order to serve responses, otherwise 404 errors will be thrown. Override this
     * method to configure routes in your app.
     */
    protected function configureHttpRouter(THttpRouter $router): void
    {
        TDebug::error('THttpRouter not configured. This will lead to 404 errors on each request. To configure, please implement configureHttpRouter(THttpRouter $router) method.');
    }

    /** 
     * Configures HTTP error handler. When an exception THttpError is thrown wherever in your app, it is passed to this error
     * handler whose task is to handle it nicely, i.e. display appropriate error page. All unhandled exceptions will be displayed
     * in development mode, however on production mode they will be turned into empty error responses.
     */
    protected function configureHttpErrorHandler(THttpErrorHandler $errorHandler): void
    {
        TDebug::error('THttpErrorHandler not configured. This will lead to unhandled THttpError exceptions.');
    }

    /**
     * Once app instance is created, call this method to run it.
     */
    final public function run()
    {
        try {
            $this->router->handleRequest();
            $target = $this->request->target;

            $this->guard->authorizeRequest($this->request);

            $this->response->setContent($target->run($this));
        } catch (Throwable $error) {
            if (!($error instanceof THttpError)) {
                if (self::isDevelopment()) {
                    throw $error;
                }

                $error = new THttpError(THttpCode::INTERNAL_SERVER_ERROR, $error->getMessage());
            }

            $target = $this->httpErrorHandler->handle($error);

            if (!$target || (self::isProduction() && !$target->exists())) {
                throw $error;
            }

            $this->response->setCode($error->getCode());

            $this->response->setContent($target->run($this));
        }
    }

    /**
     * This method is called when response is about to be sent to the client. This is the last opportunity to modify the response contents.
     */
    protected function processResponse(THttpResponse $response): void
    {
    }

    /**
     * This method is called when your app is about to terminate. This is the place where you can perform some
     * termination tasks, such as close the DB connections or so on.
     */
    protected function terminate(): void
    {
        TDebug::info('Terminating application. Implement terminate() method to perform some termination tasks.');
    }

    private function __handleUnhandledException(Throwable $e)
    {
        @ob_clean();
        if (isset($this->response)) {
            if (self::isDevelopment()) {
                TDebug::error('Unhandled exception', $e);

                TExceptionDisplay::display($e, $this->response);
            } else {
                if ($e instanceof THttpError) {
                    $this->response->setCode($e->getCode());
                } else {
                    $this->response->setCode(THttpCode::INTERNAL_SERVER_ERROR);
                }

                $this->response->setContent(null);
            }
        } else {
            if ($e instanceof THttpError) {
                header('HTTP/1.1 ' . new THttpCode($e->getCode()));
            } else {
                header('HTTP/1.1 ' . new THttpCode(THttpCode::INTERNAL_SERVER_ERROR));
            }

            if (self::isDevelopment()) {
                TDebug::error('Unhandled exception', $e);
                echo $e;
            }
        }
    }
}
