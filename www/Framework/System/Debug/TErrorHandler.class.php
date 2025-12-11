<?php
namespace System\Debug;

class TErrorHandler {
	public function handle($errno, $errstr, $errfile, $errline, $errcontext = null) {
		if(error_reporting() == 0) {
			return;
		}

		throw new TPhpError($errno, $errstr, $errfile, $errline, $errcontext); 
	}
}
