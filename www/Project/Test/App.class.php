<?php
namespace Project\Test;

use System\TApplication;
use System\Http\Router\THttpRouter;

class App extends TApplication {
    protected function configureHttpRouter(THttpRouter $router) : void {
        $router->route('home', '/')->target(
            Pages\home\home::class
        );
    }
}