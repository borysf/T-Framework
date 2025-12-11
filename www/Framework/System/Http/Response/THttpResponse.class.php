<?php
namespace System\Http\Response;

use System\Debug\TDebug;
use System\Http\Error\THttpError;
use System\Http\Request\THttpRequest;
use System\Http\THttpCode;
use System\Utils\TMimeType;

/**
 * Class representing HTTP response.
 */
class THttpResponse {
    private THttpCode $__code;
    private array $__headers = [];
    private mixed $__content = null;
    private static bool $__sent = false;
    private THttpRequest $__request;

    public function __construct(THttpRequest $request) {
        $this->__request = $request;
        $this->setCode(THttpCode::OK);
        $this->setHeader('Content-Type', 'text/html; charset='.T_DEFAULT_CHARSET);
    }

    /** Sets response header. Header name gets normalized, i.e. `content-type` => `Content-Type`. */
    public function setHeader(string $header, string $value) : void {
        $header = $this->__sanitizeHeaderName($header);

        $this->__headers[$header] = $value;
    }

    /** Unsets given response header. Header name gets normalized, i.e. `content-type` => `Content-Type`. */
    public function unsetHeader(string $header) {
        $header = $this->__sanitizeHeaderName($header);

        header_remove($header);

        if (isset($this->__headers[$header])) {
            unset($this->__headers[$header]);
        }
    }

    /** Returns header value or null if not set. Header name gets normalized, i.e. `content-type` => `Content-Type`. */
    public function getHeader(string $header) : ?string {
        $header = $this->__sanitizeHeaderName($header);

        return isset($this->__headers[$header]) ? $this->__headers[$header] : null;
    }

    /** Sets response content. Content can be null, string, array, object or object with `__toString()` method. */
    public function setContent(mixed $content) : void {
        $this->__content = $content;
    }

    /** Returns response content. */
    public function getContent() : mixed {
        return $this->__content;
    }

    /** Sets response headers. Previously set headers are dropped. Header name gets normalized, i.e. `content-type` => `Content-Type`. */
    public function setHeaders(array $headers) {
        $this->__headers = [];
        foreach ($headers as $header => $value) {
            $header = $this->__sanitizeHeaderName($header);
            $this->__headers[$header] = $value;
        }
    }

    /** Returns list of headers set for this response. */
    public function getHeaders() : array {
        return $this->__headers;
    }

    /** Sets HTTP response code. */
    public function setCode(int $code) : void {
        $this->__code = new THttpCode($code);
    }

    /** Returns HTTP response code. */
    public function getCode() : THttpCode {
        return $this->__code;
    }

    /**
     * Sends response to the user. If content is `null`, then the content previously set by `setContent()` method is sent.
     * If content is not string or object implementing `__toString()` method, automatically sets `Content-Type` to `application/json`.
     * Otherwise previously set `Content-Type` is assumed.
     */
    public function send(mixed $content = null) : void {
        if (self::$__sent) {
            return;
        }

        self::$__sent = true;

        $content = $content === null ? $this->__content : $content;

        $isString = is_string($content) || (is_object($content) && method_exists($content, '__toString'));

        if (!$isString) {
            $this->setHeader('Content-Type', 'application/json');
        }

        $this->__sendHeaders();

        if ($content !== null) {
            echo $isString ? $content : json_encode($content);
        }
        exit;
    }

    /**
     * Sends contents of provided file and automatically determines its `Content-Type`.
     * In addition, calculates `ETag` and sends `304 Not Modified` when the file has
     * not been changed.
     */
    public function sendFile(string $file, bool $etag = true, bool $lastModified = true) : void {
        if (self::$__sent) {
            return;
        }

        if (!file_exists($file) || !is_file($file)) {
            throw new THttpError(THttpCode::NOT_FOUND, $file.': no such file');
        }

        self::$__sent = true;

        TDebug::disable();

        $etagStr = null;
        $ts = null;

        if ($etag) {
            $etagStr = sha1_file($file);
        }

        if ($lastModified) {
            $lastModifiedStr = gmdate('D, d M Y H:i:s ', filemtime($file)) . 'GMT';
            $this->setHeader('Last-Modified', $lastModifiedStr);
        }

        if (
            ($etagStr && isset($this->__request->headers['If-None-Match']) && $this->__request->headers['If-None-Match'] == $etagStr) ||
            ($lastModifiedStr && isset($this->__request->headers['If-Modified-Since']) && $this->__request->headers['If-Modified-Since'] == $lastModifiedStr)
        ) {
            $this->setCode(THttpCode::NOT_MODIFIED);
            $this->send('');
        }

        if ($etagStr) {
            $this->setHeader('ETag', $etagStr);
        }

        $this->setHeader('Content-Type', TMimeType::get($file));

        $this->__sendHeaders();

        readfile($file);
        exit;
    }

    private function __sendHeaders() : void {
        header_remove('X-Powered-By');

        header('HTTP/1.1 '.$this->__code);

        foreach ($this->__headers as $header => $value) {
            if ($header == 'Content-Type' && preg_match('{^(?:text/)|(?:application/(?:javascript|json))}', $value)) {
                if (!preg_match('{;\s*charset=.*$}', $value)) {
                    $value = $value.'; charset='.T_DEFAULT_CHARSET;
                }
            }
            header($header.': '.$value);
        }
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
