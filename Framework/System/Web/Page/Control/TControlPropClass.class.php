<?php
namespace System\Web\Page\Control;

use ArrayObject;
use System\Exception\TInvalidValueException;

/**
 * Represents `TControl`'s `html.class` prop. Implements handy interface allowing
 * easy manipulation of the class names.
 */
class TControlPropClass extends ArrayObject {
    
    /** Sets new class list */
	public function setValue(string $classNames) : void {
        $this->exchangeArray(array_unique($this->__split($classNames)));
    }
    
    /** Inserts new classes at given offset. Classes that are already in the list are skipped. */
	public function offsetSet(mixed $offset, mixed $classNames) : void {
        $array = $this->getArrayCopy();

        if ($offset !== null) {
            if (!is_int($offset)) {
                throw new TInvalidValueException(gettype($offset), reason: 'Offset must be int, but '.gettype($offset).' given');
            }
            if (!is_string($classNames) && !(is_object($classNames) && method_exists($classNames, '__toString'))) {
                throw new TInvalidValueException(gettype($offset), reason: 'Value must be string or object with __toString() method, but '.gettype($classNames).' given');
            }

            $i = 0;

            foreach ($this->__split($classNames) as $class) {
                if (!in_array($class, $array)) {
                    array_splice($array, $offset + ($i++), 0, $class);
                }
            }
        } else {
            foreach ($this->__split($classNames) as $class) {
                if (!in_array($class, $array)) {
                    $array[] = $class;
                }
            }
        }

        $this->exchangeArray($array);
	}
	
    /** Adds new class (or space-separated classes) to the list */
    public function add(string $name) : void {
        $array = $this->getArrayCopy();

        foreach ($this->__split($name) as $class) {
            if (!in_array($class, $array)) {
                $this->append($class);
            }
        }
    }

    /** Removes given class (or space-separated classes) from the list */
    public function remove(string $name) : void {
        $array = $this->getArrayCopy();

        foreach ($this->__split($name) as $class) {    
            $pos = array_search($class, $array);

            if ($pos !== false) {
                array_splice($array, $pos, 1);
            }
        }

        $this->exchangeArray($array);
    }

    /** Returns string representation of the class list. */
	public function __toString() : string {
		return implode(' ', $this->getArrayCopy());
	}

    private function __split(string $classNames) {
        return preg_split('{^\s+}', trim($classNames));
    }
}