<?php

namespace System\Database\DbObject;

use ArrayAccess;
use Countable;
use IteratorIterator;
use System\Database\TDatabase;
use System\Database\TDatabaseConnection;
use System\Database\TDatabaseQueryResult;

class TDbObjectCollection extends IteratorIterator implements Countable, ArrayAccess
{
    private $_count;
    private $_rows;
    private $_itemClass;

    public function __construct(int $count, TDatabaseQueryResult $rows, string $itemClass, string $itemTable, string $itemPrimaryKey)
    {
        parent::__construct($rows);
        $this->_count = $count;
        $this->_itemClass = $itemClass;
        $this->_rows = $rows;
        $this->_db = new TDatabase($itemClass::DB_CONNECTION_NAME);
        $this->_table = $itemTable;
        $this->_pk = $itemPrimaryKey;
    }

    public function delete(): void
    {
        $ids = array();
        $placeholders = array();

        foreach ($this as $row) {
            $ids[] = $row->id();
            $placeholders[] = '?';
        }

        if (!empty($ids)) {
            $this->_db->Query('DELETE FROM `' . $this->_table . '` WHERE `' . $this->_pk . '` IN (' . implode(', ', $placeholders) . ')', $ids);
        }
    }

    public function count(): int
    {
        return $this->_count;
    }

    public function current(): mixed
    {
        $class = $this->_itemClass;
        return new $class(null, parent::current());
    }

    public function __serialize(): array
    {
        return array($this->_count, $this->_rows, $this->_itemClass, $this->_table, $this->_pk);
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(...$data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->_count;
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new TDbObjectException('Out of bounds: ' . $offset);
        }

        $class = $this->_itemClass;
        return new $class(null, $this->_rows[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new TDbObjectException('Collection is readonly');
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new TDbObjectException('Collection is readonly');
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function toArray($fieldsFilter = null): array
    {
        $ret = array();

        foreach ($this as $row) {
            $ret[] = $row->toArray($fieldsFilter);
        }

        return $ret;
    }
}
