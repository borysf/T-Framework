<?php
require 'Framework/TAutoloader.class.php';

use System\Http\Request\THttpRequest;
use System\Http\UriMatcher\THttpUriMatcher;

use Docs\DocsApp;
use Project\Test\App;

$matcher = new THttpUriMatcher;

// Framework docs: local development only
$matcher->host('^localhost$')->path('^/__docs/')->onMatch(function (THttpRequest $request) {
    $app = new DocsApp('/__docs/', DocsApp::MODE_DEVELOPMENT);
    $app->run();
});

// Info about PHP: local development only
$matcher->host('^localhost')->path('^/__info/')->onMatch(function (THttpRequest $request) {
    phpinfo();
});

$matcher->host('^localhost$')->path('^/test/')->onMatch(function (THttpRequest $request) {
    $app = new App('/test/', App::MODE_DEVELOPMENT);
    $app->run();
});

if (!$matcher->run()) {
    die($matcher->request->host() . ': no matching rule found');
}
