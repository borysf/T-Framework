<?php
namespace System\Http\Session;

class THttpSessionProvider implements IHttpSessionProvider {
    public function start(array $options = []) : void {
        @session_start($options);
    }

	public function read(string $key, $default = null) {
        if (!isset($_SESSION)) {
            return $default;
        }

        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }
    
	public function write(string $key, $value) : void {
        if (isset($_SESSION)) {
            $_SESSION[$key] = $value;
        }
    }

	public function clear(string $key) : void {
        if (isset($_SESSION) && array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
        }
    }
    
    public function destroy() : void {
        session_destroy();
    }
}