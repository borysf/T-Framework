<?php
namespace System\Debug;

class TPhpError extends \Exception
{
	public function __construct($errno, $errstr, $errfile, $errline, $errcontext)
	{
		$this->message = $errstr;
		$this->code = $errno;
		$this->file = $errfile;
		$this->line = $errline;
	}
}
