<?php
namespace Docs;

use System\Http\Error\THttpErrorHandler;
use System\TApplication;
use System\Http\Router\THttpRouter;

/**
 * T Documentation application.
 * 
 * This application displays automatically-built source code documentation of the framework.
 */
class DocsApp extends TApplication {
    protected function configureHttpRouter(THttpRouter $router) : void {
        $router->route('system:docs:property', '/{path}/property/{name}', [
            'path' => '[a-zA-Z0-9\._]+',
            'name' => '[a-zA-Z0-9_]+',
        ])->target(docs\docs::class, 'propertyDocs');

        $router->route('system:docs:method', '/{path}/method/{name}', [
            'path' => '[a-zA-Z0-9\._]+',
            'name' => '[a-zA-Z0-9_]+',
        ])->target(docs\docs::class, 'methodDocs');

        $router->route('system:docs:path', '/{path}/', [
            'path' => '[a-zA-Z0-9\._]+'
        ])->target(docs\docs::class, 'classDocs');

        $router->route(
            'system:docs:example:pages-extending', 
            '/__examples/pages-extending'
        )->target(examples\pagesExtendingSub::class);

        $router->route(
            'system:docs:example:viewstate-button-increment', 
            '/__examples/viewstate-button-increment'
        )->target(examples\viewstateButtonIncrement::class);
        
        $router->route('system:docs:main', '/', [])->target(home\home::class); 
    }


    protected function configureHttpErrorHandler(THttpErrorHandler $errorHandler) : void {
        $errorHandler->on(404)->target(errorPage\errorPage::class, 'handle404');
        $errorHandler->on(500)->target(errorPage\errorPage::class, 'handle500');
    }
}