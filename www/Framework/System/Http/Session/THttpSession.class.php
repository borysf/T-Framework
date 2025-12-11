<?php
namespace System\Http\Session;

use System\Debug\TDebug;

class THttpSession {
    private IHttpSessionProvider $__provider;
    private array $__options;
    public readonly bool $__started;

    public function __construct(IHttpSessionProvider $provider, $options = []) {
        $this->__provider = $provider;
        $this->__options = $options;
    }

    public function isStarted() : bool {
        return isset($this->__started) && $this->__started;
    }

    public function startIfCookiePresent() : void {
        $cookieName = isset($this->__options['name']) ? $this->__options['name'] : 'PHPSESSID';

        if (isset($_COOKIE[$cookieName])) {
            $this->start();
        }
    }

    public function start() : void {
        if (!isset($this->__started)) {
            TDebug::log('Session: start', $this->__options);
            $this->__provider->start($this->__options);
            $this->__started = true;
        }
    }

    public function read(string $key, $default = null) : mixed {
        $this->start();
        return $this->__provider->read($key, $default);
    }

	public function write(string $key, $value) : void {
        $this->start();
        $this->__provider->write($key, $value);
    }

	public function clear(string $key) : void {
        $this->__provider->clear($key);
    }

    public function destroy() : void {
        $this->__provider->destroy();
    }
}