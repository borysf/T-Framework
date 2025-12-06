<?php
namespace System\Http\Request;

use ArrayAccess;
use Exception;
use System\TException;

class THttpRequestParams implements ArrayAccess {
    private array $_source;

    public function __construct(array $source = []) {
        $this->_source = &$source;
    }

    public function __get($name) {
        return $this->_source[$name];
    }

    public function __set($name, $value) {
        $this->_source[$name] = $value;
    }

    public function __isset($name) {
        return isset($this->_source[$name]);
    }

    public function __unset($name) {
        unset($this->_source[$name]);
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->_source[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->_source[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        throw new TException('Request data is read-only');
    }

    public function offsetUnset(mixed $offset): void {
        throw new TException('Request data is read-only');
    }

    public function toArray() : array {
        return $this->_source;
    }
}