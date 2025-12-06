<?php
namespace System\Debug;

use System\Http\Error\THttpError;
use System\Http\Response\THttpResponse;
use System\Http\THttpCode;
use System\TApplication;
use System\TException;
use System\Web\Page\Control\Template\Compiler\TTemplateCompilerException;
use Throwable;

class TExceptionDisplay {
	public static function display(Throwable $e, THttpResponse $response) {
		if ($e instanceof THttpError) {
            $response->setCode($e->getCode());
        } else {
            $response->setCode(THttpCode::INTERNAL_SERVER_ERROR);
        }

		if (preg_match('{^application/json}', $response->getHeader('Content-Type'))) {
			self::__displayJson($e, $response);
		} else if (preg_match('{^text/javascript}', $response->getHeader('Content-Type'))) {
			self::__displayJs($e, $response);
		} else {
			$response->setHeader('Content-Type', 'text/html');
			self::__displayHtml($e, $response);
		}
	}

	private static function __displayJson(Throwable $e, THttpResponse $response) {
		$response->setContent(['exception' => [
			'class' => $e::class,
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'reason' => isset($e->reason) ? $e->reason : null,
			'code' => $e->getCode()
		]]);
	}

	private static function __displayJs(Throwable $e, THttpResponse $response) {
		$response->setContent('console.error('.json_encode(['exception' => [
			'class' => $e::class,
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'message' => $e->getMessage(),
			'reason' => isset($e->reason) ? $e->reason : null,
			'code' => $e->getCode()
		]]).');');
	}

	private static function __displayHtml(Throwable $e, THttpResponse $response) {
		$stack = explode("\n", $e->getTraceAsString());
		
		$stackStr = '<div class="stack-trace">'.implode('</div><div class="stack-trace">', $stack).'</div>';
		$source = '';

		if (($e instanceof TException) && $e->customFileName && $e->customLineNo && is_file($e->customFileName)) {
			$sourceFileName = $e->customFileName;
			$sourceLineNo = $e->customLineNo;
		} else {
			$sourceFileName = $e->getFile();
			$sourceLineNo = $e->getLine();
		}

		if (file_exists($sourceFileName)) {
			$sourceLines = file($sourceFileName);
			$numLines = count($sourceLines);
			
			$offset = $sourceLineNo - 10;
			$length = 20;
			
			if($offset < 0) $offset = 0;
			
			$sourceLines = array_slice($sourceLines, $offset, $length, true);
			
			foreach($sourceLines as $lineNo => $line)
			{
				$css = $lineNo%2 == 0 ? 'line-1' : 'line-2';
				$css = $lineNo+1 == $sourceLineNo ? 'error-line' : $css;
				
				$line = str_replace('   ', "\t", $line); //4 spaces => 1 tab
				$line = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($line)); //1 tab => 4 nbsps
				
				$source .= '<div class="'.$css.'">'.$line.'</div>';
			}
		}
		
		$title = 'Error in \''.TApplication::getRootUriPath().'\' application';
		
		@ob_end_clean();
		
		$response->setContent('<html><head>
		<meta name="content-type" content="text/html; charset=UTF-8" />
		<title>'.$title.'</title>
		<style type="text/css">
			h1 {color:red; font-weight:normal;margin-top:0}
			h2 {font-weight:normal}
			body {font-family:verdana,helvetica,arial; font-size:15px;background-color:#F5F5F5}
			div.footer {margin-top:30px;font-size:10px;color:gray;border-top:solid silver 1px}
			div.line-1, div.line-2, div.error-line, div.stack-trace {font-size:12px;padding:3px}
			div.error-line {color:red;background-color:rgb(255,224,224)}
			div.error-panel {border:solid silver 1px;padding:10px;background-color:white}
			div.error-container {margin:20px}
			div.source-container {font-family:monospace;background-color:rgb(255,255,245);border:dashed rgb(212,212,138) 1px;padding:5px}
			/*div.line-1 {background-color:rgb(255,255,232)}*/
		</style>
		</head>
		
		<body>

			<div class="error-container">
				<div class="error-panel">
					<h1>'.$title.'</h1>
					
					<h2><strong>Uncaught '.$e::class.'</strong>
						<p>'.str_replace("\n", '<br />', htmlspecialchars($e->getMessage())).'</p>
					</h2>
			
					'.(isset($e->reason) ? ('<p><strong>Reason:</strong></p><div>'.htmlspecialchars($e->reason).'</div>') : '').'

					<p><strong>Thrown in:</strong></p>
					<p>'.$sourceFileName.' on line <strong>'.$sourceLineNo.'</strong></p>
			
					<p><strong>Source preview:</strong></p>
					<div class="source-container">			
					'.$source.'
					</div>

					<p><strong>Stack trace:</strong></p>
			
					<div class="stack-trace">
					'.$stackStr.'
					</div>
			
					<div class="footer">
					'.$_SERVER['SERVER_SOFTWARE'].' / PHP '.PHP_VERSION.'
					</div>
				</div>			
			</div>
		</body>
		</html>
		');
	}
}
