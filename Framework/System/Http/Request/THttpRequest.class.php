<?php
namespace System\Http\Request;

use System\Http\Router\THttpRouteTarget;

/**
 * Class representing incoming HTTP request to the server.
 */
class THttpRequest {
	/** Request arguments processed by router. */
	public readonly THttpRequestParams $args;

	/** Request GET variables. */
    public readonly THttpRequestParams $get;

	/** Request POST variables. */
	public readonly THttpRequestParams $post;

	/** Request cookies. */
    public readonly THttpRequestParams $cookie;

	/** Session variables. */
	public readonly THttpRequestParams $session;

	/** List of headers. */
	public readonly THttpRequestParams $headers;

	/** Route target matched to this request. */
	public readonly THttpRouteTarget $target;

	/** JSON contents if request method was `PUT` or `POST` and `Content-Type` was set to `application/json`. */
	public readonly mixed $json;

    public function __construct() {
		$headers = getallheaders();
        $this->get = new THttpRequestParams($_GET);
        $this->post = new THttpRequestParams($_POST);
        $this->cookie = new THttpRequestParams($_COOKIE);
        // $this->session = new THttpRequestParams(isset($_SESSION) ? $_SESSION : []);

		$this->headers = new THttpRequestParams(array_combine(array_map(fn($key) => $this->__sanitizeHeaderName($key), array_keys($headers)), $headers));

		if (in_array($this->method(), ['POST', 'PUT']) && isset($this->headers['Content-Type']) && preg_match('{^application/json}i', $this->headers['Content-Type'])) {
			$this->json = json_decode(file_get_contents('php://input'));
		} else {
			$this->json = null;
		}
    }

	public function __fillSessionData() {
		$this->session = new THttpRequestParams(isset($_SESSION) ? $_SESSION : []);
	}

	/**
	 * Returns header by its name. Header name gets normalized, i.e. `content-type` => `Content-Type`, as stored in `$headers` list.
	 */
	public function getHeader(string $header) : ?string {
		$header = $this->__sanitizeHeaderName($header);
		return isset($this->headers[$header]) ? $this->headers[$header] : null;
	}

	/** Sets route target. Used internally by `THttpRouter`. */
	public function setTarget(THttpRouteTarget $target) {
		$this->target = $target;
	}

	/** Sets route arguments. Used internally by `THttpRouter`. */
	public function setArgs(array $args) {
		$this->args = new THttpRequestParams($args);
	}

	/** Returns HTTP host for this request. */
    public function host() : ?string {
		return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
	}

	/** Returns HTTP host without port for this request. */
    public function hostname() : ?string {
		$host = $this->host();

		return $host && ($pos = strrpos($host, ':')) ? substr($host, 0, $pos) : $host;
	}

	/** Returns server port for this request. */
	public function port() : ?int {
		return isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
	}

	/** Returns HTTP request method for this request. */
	public function method() : ?string {
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
	}

	/** Returns protocol for this request. */
	public function protocol() : ?string {
		return isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;
	}

	/** Returns time on which this request arrived. */
	public function requestTime() : ?string {
		return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : null;
	}

	/** Returns raw query string from request URL. */
	public function queryString() : ?string {
		return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
	}

	public function accept() : ?string {
		return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
	}

	public function acceptCharset() : ?string {
		return isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : null;
	}

	public function acceptEncoding() : ?string {
		return isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null;
	}

	public function acceptLanguage() : ?string {
		return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
	}

	public function connection() : ?string {
		return isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : null;
	}

	public function referer() : ?string {
		return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	}

	public function userAgent() : ?string {
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
	}

	public function https() : bool {
		return (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) ||
            (isset($_SERVER['HTTP_X_FORWARDED_SERVER_PORT']) && $_SERVER['HTTP_X_FORWARDED_SERVER_PORT'] == 443) ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
            $this->port() == 443;
	}

	public function remoteAddr() : ?string {
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
	}

	public function remotePort() : ?int {
		return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
	}

	public function uri() : ?string {
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
	}

	public function path() : ?string {
		return isset($_SERVER['DOCUMENT_URI']) ? $_SERVER['DOCUMENT_URI'] : null;
	}

	public function authDigest() : ?string {
		return isset($_SERVER['PHP_AUTH_DIGEST']) ? $_SERVER['PHP_AUTH_DIGEST'] : null;
	}

	public function authUser() : ?string {
		return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
	}

	public function authPassword() : ?string {
		return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
	}

	public function ajaxRequest() : ?string {
		return (isset($_SERVER['X_REQUESTED_WITH']) && $_SERVER['X_REQUESTED_WITH'] == 'XMLHttpRequest');
	}

	private function __sanitizeHeaderName(string $header) : string {
        $header = trim(strtolower($header));

        $header[0] = strtoupper($header[0]);
        $header = preg_replace_callback('{-([a-z])}', function ($match) {
            return '-'.strtoupper($match[0][1]);
        }, $header);

        return $header;
    }
}
