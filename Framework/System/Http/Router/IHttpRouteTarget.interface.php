<?php
namespace System\Http\Router;

use System\Http\Request\THttpRequest;
use System\Http\Response\THttpResponse;
use System\TApplication;

interface IHttpRouteTarget {
    public function run(TApplication $app) : string;
}