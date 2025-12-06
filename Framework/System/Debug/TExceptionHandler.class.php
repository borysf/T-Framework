<?php
namespace System\Debug;

use System\Debug\TExceptionDisplay;
use System\Http\Error\THttpError;
use System\Http\THttpCode;

class TExceptionHandler {
	public function handle($e) {
		if ($e instanceof THttpError) {
			$code = new THttpCode($e->getCode());
		} else {
			$code = new THttpCode(THttpCode::INTERNAL_SERVER_ERROR);
		}

		header('HTTP/1.1 '.$code);
		@ob_end_clean();

		echo TExceptionDisplay::displayException($e);
		exit;
	}
}