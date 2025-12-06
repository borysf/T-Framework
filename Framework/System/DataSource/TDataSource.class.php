<?php
namespace System\DataSource;

use ArrayAccess;
use Countable;
use Iterator;
use System\Component\TComponent;
use System\Web\Page\Control\Event\TEventArgs;

class TDataSourceRow {
    public readonly mixed $value;
    public readonly int $key;

    public function __construct(mixed &$value, int $key) {
        $this->value = &$value;
        $this->key = &$key;
    }
}

class TDataSource extends TComponent implements ArrayAccess, Countable, Iterator {
    protected array $_data;
    protected array $_keys;
    protected int $_pointer;
    protected int $_key;
    protected array $_accessed = [];

    public function __construct(array $data) {
        $this->_data = $data;
        $this->_keys = array_keys($data);
        $this->_pointer = 0;
        $this->_key = 0;

        $this->_initEvents();
    }

    private function _initEvents() {

    }

    public function offsetSet(mixed $offset, mixed $value): void {
        $this->_data[$offset] = $value;
        $this->_accessed[$offset] = new TDataSourceRow($value, isset($this->_accessed[$offset]) ? $this->_accessed[$offset] : $this->_key++);
    }

    public function offsetExists(mixed $offset): bool {
        if (array_key_exists($offset, $this->_data)) {
            if (!isset($this->_accessed[$offset])) {
                $this->_accessed[$offset] = new TDataSourceRow($this->_data[$offset], $this->_key++);
            }

            return true;
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed {
        if ($this->offsetExists($offset)) {
            return $this->_accessed[$offset];
        }
    }

    public function offsetUnset(mixed $offset): void {
        if ($this->offsetExists($offset)) {
            unset($this->_data[$offset]);
            unset($this->_accessed[$offset]);
        }
    }

    public function count() : int {
        return count($this->_data);
    }

    public function __serialize(): array {
        return ['_data' => &$this->_accessed, '_key' => &$this->_key];
    }

    public function __unserialize(array $data): void {
        $this->_initEvents();
        if (isset($data['_data'])) {
            $this->_data = [];
            $this->_key = $data['_key'];
            $this->_keys = array_keys($data['_data']);
            $this->_pointer = 0;

            foreach ($data['_data'] as $k => $v) {
                $this->_data[$k] = $v->value;
            }
            foreach ($this->_data as $k => $v) {
                $this->_accessed[$k] = new TDataSourceRow($this->_data[$k], $data['_data'][$k]->key);
            }
        }
    }

    
    public function current(): mixed {
        return $this->offsetGet($this->_keys[$this->_pointer]);
    }

    public function next(): void {
        ++$this->_pointer;
    }

    public function rewind(): void {
        $this->_pointer = 0;
    }

    public function key(): mixed {
        return $this->_keys[$this->_pointer];
    }

    public function valid(): bool {
        return isset($this->_keys[$this->_pointer]) && $this->offsetExists($this->_keys[$this->_pointer]);
    }

    public function add(mixed $data, ?int $index = null) {
        $value = new TDataSourceRow($data, $this->_key++);

        if ($index === null) {
            $this->_data[] = &$data;
            $this->_accessed[] = &$value;
        } else {
            array_splice($this->_data, $index, 0, array($data));
            array_splice($this->_accessed, $index, 0, array($value));
        }

        $args = new TDataSourceEventArgs($index !== null ? $index : count($this->_accessed) - 1, $value);

        $this->raise('onAdd', $args);
    }

    public function remove(?int $key = null) {
        if (!isset($this->_accessed) || empty($this->_accessed)) {
            return;
        }

        $index = null;

        if ($key === null) {
            $index = array_keys($this->_accessed)[0];
        } else if ($key == -1) {
            $keys = array_keys($this->_accessed);
            $index = end($keys);
        } else {
            foreach ($this->_accessed as $k => $v) {
                if ($v->key == $key) {
                    $index = $k;
                    break;
                }
            }
        }

        if ($index === null) {
            throw new TDataSourceException('Could not find entry with key: '.$key);
        }

        $value = $this->offsetGet($index);
        
        if ($index === null) {
            array_pop($this->_data);
            array_pop($this->_accessed);
        } else {
            array_splice($this->_data, $index, 1);
            array_splice($this->_accessed, $index, 1);
        }

        $args = new TDataSourceEventArgs($index === null ? count($this->_accessed) : $index, $value);

        $this->raise('onRemove', $args);
    }

    public function removeAll() : void {
        if (!empty($this->_data)) {
            $this->_data = [];
            $this->_accessed = [];
        }

        $this->raise('onRemoveAll', new TDataSourceEventArgs(null, null));
    }

    protected function onAdd(?TEventArgs $args) {}
    protected function onRemove(?TEventArgs $args) {}
    protected function onRemoveAll(?TEventArgs $args) {}
}

//     private readonly string $_type;
//     private array|object $_data;
//     private readonly array $_keys;
//     private int|string $_pointer;
//     private readonly int $_count;
//     private array|object|null $_accessed;

//     public function __construct(array|object $data) {
//         $this->_load($data);
//     }

//     private function _load($data) {
//         $this->_data = &$data;
//         $this->_type = gettype($data);
//         $this->_accessed = null;
//         $this->_keys = $this->isObject() ? array_keys(get_object_vars($data)) : array_keys($data);
//         $this->_count = $this->isObject() ? count($this->_keys) : count($data);
//     }

//     public function isArray() {
//         return is_array($this->_data) || $this->_data instanceof ArrayAccess;
//     }

//     public function isArrayObject() {
//         return $this->_data instanceof ArrayAccess;
//     }

//     public function isObject() : bool {
//         return is_object($this->_data) && !($this->_data instanceof ArrayAccess);
//     }

//     private function _isPrimitive(mixed $value) : bool {
//         return is_scalar($value) || $value === null;
//     }

//     public function getAccessedCopy() : array|object {
//         $v = $this->_getAccessedCopy();

//         if ($v instanceof NoneValue) {
//             return $this->isArray() ? [] : new stdClass;
//         }

//         return $v;
//     }

//     protected function _getAccessedCopy() : array|object {
//         if (isset($this->_accessed)) {
//             $data = $this->isArray() ? [] : new stdClass;

//             foreach ($this->_accessed as $k => $v) {
//                 $v = $v instanceof TDataSourceValue ? $v->value : $v->_getAccessedCopy();
                
//                 if ($v instanceof NoneValue) {
//                     continue;
//                 }

//                 if ($this->isArray()) {
//                     $data[$k] = $v;
//                 } else {
//                     $data->$k = $v;
//                 }
//             }

//             return $data;
//         }

//         return new NoneValue;
//     }

//     private function _createSimpleCopy(): array|object {
//         $copy = $this->isArray() ? [] : new stdClass;

//         foreach ($this->_data as $k => $v) {
//             if ($this->isArray()) {
//                 $copy[$k] = $this->_copy($v);
//             } else {
//                 $copy->$k = $this->_copy($v);
//             }
//         }

//         return $copy;
//     }

//     private function _copy(mixed $value): mixed {
//         if (is_array($value) || $value instanceof ArrayAccess) {
//             $copy = [];
            
//             foreach ($value as $k => $v) {
//                 $copy[$k] = $this->_copy($v);
//             }
            
//             return $copy;
//         }
        
//         if (is_object($value)) {
//             $copy = new stdClass;
            
//             foreach ($value as $k => $v) {
//                 $copy->$k = $this->_copy($v);
//             }

//             return $copy;
//         }
        
//         if (is_scalar($value) || $value === null) {
//             return $value;
//         }
//     }

//     public function offsetExists(mixed $offset): bool {
//         if ($this->isArray()) {
//             if (isset($this->_data[$offset])) {
//                 $this->_accessed[$offset] = $this->_isPrimitive($this->_data[$offset]) ? new TDataSourceValue($this->_data[$offset]) : new TDataSource($this->_data[$offset]);
                
//                 return true;
//             }
//         } else {
//             if (isset($this->_data->$offset)) {
//                 $this->_accessed[$offset] = $this->_isPrimitive($this->_data->$offset) ? new TDataSourceValue($this->_data->$offset) : new TDataSource($this->_data->$offset);
                
//                 return true;
//             }
//         }

//         print($offset);

//         return false;
//     }

//     public function offsetGet(mixed $offset): mixed {
//         if ($this->isArray()) {
//             if (isset($this->_data[$offset])) {
//                 $this->_accessed[$offset] = $this->_isPrimitive($this->_data[$offset]) ? new TDataSourceValue($this->_data[$offset]) : new TDataSource($this->_data[$offset]);
            
//                 return $this->_accessed[$offset] instanceof TDataSourceValue ? $this->_accessed[$offset]->value : $this->_accessed[$offset];
//             }

//         } else {
//             if (isset($this->_data->$offset)) {
//                 $this->_accessed[$offset] = $this->_isPrimitive($this->_data->$offset) ? new TDataSourceValue($this->_data->$offset) : new TDataSource($this->_data->$offset);
            
//                 return $this->_accessed[$offset] instanceof TDataSourceValue ? $this->_accessed[$offset]->value : $this->_accessed[$offset];
//             }
//         }

//         throw new TDataSourceException('Undefined offset: '.$offset);
//     }

//     public function offsetSet(mixed $offset, mixed $value): void {
//         throw new TDataSourceException('DataSource is readonly');
//     }

//     public function offsetUnset(mixed $offset): void {
//         throw new TDataSourceException('DataSource is readonly');
//     }

//     public function __get(string $key): mixed {
//         if ($this->isObject() && $this->offsetExists($key)) {
//             return $this->_accessed[$key] instanceof TDataSourceValue ? $this->_accessed[$key]->value : $this->_accessed[$key];
//         }
//         throw new TDataSourceException('Underlying data is not an object or has no property '.$key);
//     }

//     public function __set(string $key, mixed $value): void {
//         throw new TDataSourceException('DataSource is readonly');
//     }

//     public function __isset(string $key): bool {
//         return $this->offsetExists($key);
//     }

//     public function __unset(string $key): void {
//         throw new TDataSourceException('DataSource is readonly');
//     }


//     public function count(): int {
//         return count($this->_data);
//     }

    
//     public function __serialize(): array {
//         return ['_data' => $this->getAccessedCopy()];
//     }

//     public function __unserialize(array $data): void {
//         if (isset($data['_data'])) {
//             $this->_load($data['_data']);
//         }
//     }

    
//     public function current(): mixed {
//         return $this->offsetGet($this->_keys[$this->_pointer]);
//     }

//     public function next(): void {
//         ++$this->_pointer;
//     }

//     public function rewind(): void {
//         $this->_pointer = 0;
//     }

//     public function key(): mixed {
//         return $this->_keys[$this->_pointer];
//     }

//     public function valid(): bool {
//         return isset($this->_keys[$this->_pointer]) && $this->offsetExists($this->_keys[$this->_pointer]);
//     }
// }