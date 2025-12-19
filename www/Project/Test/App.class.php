<?php
namespace Project\Test;

use System\Debug\TDebug;
use System\Http\Auth\THttpAuthProvider;
use System\Http\Response\THttpResponse;
use System\TApplication;
use System\Http\Router\THttpRouter;
use System\Http\Security\Guard\THttpGuard;
use System\Security\Auth\IAuthProvider;
use System\Web\Page\Control\State\IViewStateProvider;
use System\Web\Page\Control\State\TViewStateProvider;
use tidy;

class App extends TApplication {
    protected function configureHttpRouter(THttpRouter $router) : void {
        $router->route('home', '/')->target(
            Pages\home\home::class
        );
    }

    protected function configureHttpGuard(THttpGuard $guard): void
    {
        // $guard->enableCsrfProtection();
    }

    protected function createViewStateProvider(): IViewStateProvider
    {
        return new TViewStateProvider('l0r3MipsUmDo10rS1t!');
    }

    protected function createAuthProvider(): IAuthProvider
    {
        return new THttpAuthProvider();
    }

    protected function processResponse(THttpResponse $response): void
    {
        if (preg_match('{^text/html}', $response->getHeader('Content-Type')) && ($content = $response->getContent())) {
            TDebug::log('Tidying-up output HTML');
            $t = new tidy();

            $t->parseString($content, [
                'indent'                => true,
                'output-html'           => true,
                'merge-divs'            => false,
                'merge-emphasis'        => false,
                'merge-spans'           => false,
                'omit-optional-tags'    => false,
                'drop-empty-elements'   => false,
                'wrap'                  => 300,
                'show-errors'           => false,
                'show-warnings'         => false,
                'show-info'             => false
            ]);
            $t->cleanRepair();

            $response->setContent('' . $t);
        }
    }
}