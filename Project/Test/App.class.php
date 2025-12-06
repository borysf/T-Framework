<?php
namespace Project\Test;

use System\Database\TDatabaseConnection;
use System\TApplication;
use System\Http\Error\THttpErrorHandler;
use System\Http\Router\THttpRouter;
use System\Http\Security\Guard\THttpGuard;

class App extends TApplication {
    protected function configureHttpRouter(THttpRouter $router) : void {
        $router->route('home', '/')->target(
            Pages\home\home::class
        );
    }
}