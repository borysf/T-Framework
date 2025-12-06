<?php
namespace System\Web\Page\Control;

use ArrayAccess;

/**
 * Represents `TControl`'s `html.style` prop. Implements handy interface
 * to manipulate inline styles easily.
 */
class TControlPropStyle implements ArrayAccess {
	private array $_style = [];
	
	/** Sets new style value */
	public function setValue(string $style) : void {
		$parts = explode(';', $style);

		$this->_style = [];
		
		if (!empty($parts)) foreach($parts as $part) {
			$part = trim($part);
			if ($pos = strpos($part,':')) {
				$attrib = trim(strtolower(substr($part,0,$pos)));
				$value = trim(substr($part,$pos+1));
				
				$this->_style[$attrib] = $value;
			}
		}
	}
	
	public function offsetSet(mixed $name, mixed $value) : void {
		$this->__set($name, $value);
	}
	
	public function offsetGet(mixed $name) : string {
		return $this->__get($name);
	}
	
	public function offsetUnset(mixed $name) : void {
		$name = strtolower($name);
		
		if (isset($this->_style[$name])) {
			unset($this->_style[$name]);
		}
	}
	
	public function offsetExists(mixed $name) : bool {
		$name = strtolower($name);
		
		return isset($this->_style[$name]);
	}
	
	public function __set($attrib, $value) : void {
		$attrib = preg_replace_callback('{([A-Z])}', fn($match) => '-'.strtolower($match[1]), $attrib);
		$attrib = strtolower($attrib);
		
		$this->_style[$attrib] = $value;
	}
	
	public function __get($attrib) : string {
		$attrib = preg_replace_callback('{([A-Z])}', fn($match) => '-'.strtolower($match[1]), $attrib);
		$attrib = strtolower($attrib);
		
		return $this->_style[$attrib];
	}
	
	public function __toString() : string {
		$ret = '';
		
		foreach($this->_style as $attrib => $value) {
			$ret .= ($value ? "$attrib:$value;" : '');
		}
		
		return $ret;
	}
}