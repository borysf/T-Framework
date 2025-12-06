<?php
namespace System\Database;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use PDOStatement;
use System\TException;
use Traversable;

class TDatabaseQueryResult implements ArrayAccess, Countable, IteratorAggregate {
    private array $result;
    private int $count;

    public function __construct(?PDOStatement $statement = null) {
        $this->result = $statement ? $statement->fetchAll() : [];
        $this->count = $statement ? $statement->rowCount() : 0;
    }

    public function offsetExists(mixed $offset): bool {
        return $offset < $this->count && $offset >= 0;
    }

    public function offsetGet(mixed $offset): mixed {
        if (!$this->offsetExists($offset)) {
            throw new TException('Out of bounds: '.$offset);
        }

        return $this->result[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value) : void {
        throw new TException('Database query result is read only');
    }

    public function offsetUnset(mixed $offset) : void {
        throw new TException('Database query result is read only');
    }

    public function count() : int {
        return $this->count;
    }

    public function __serialize() : array {
        return $this->result;
    }

    public function __unserialize(array $data) : void {
        $this->result = $data;
        $this->count = count($data);
    }

    public function getIterator(): Traversable {
        return new ArrayIterator($this->result);
    }
}