<?php
namespace System\Http\Session;

interface IHttpSessionProvider {
    public function start(array $options = []) : void;
	public function read(string $key, $default = null);
	public function write(string $key, $value) : void;
	public function clear(string $key) : void;
    public function destroy() : void;
}