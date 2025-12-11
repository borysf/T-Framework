<?php

namespace System\Database\DbObject;

use ArrayAccess;
use Countable;
use ReflectionClass;
use System\Database\TDatabase;
use System\Database\TDatabaseQueryResult;
use System\TException;

class TDbObject implements ArrayAccess, Countable
{
    const DB_CONNECTION_NAME = 'default';

    const DB_TABLE = null;
    const DB_PRIMARY_KEY = null;
    const DB_FIELDS = array();
    const VR_FIELDS = array();

    private $DB_PRIMARY_KEY = null;
    private $DB_FIELDS = array();

    private $_db_fields = array();
    private $_vr_fields = array();
    private $_dirty = array();

    public function __construct($id = null, $data = array())
    {
        if (!$this::DB_TABLE) {
            throw new TDbObjectException('const DB_TABLE must be defined');
        }

        if (empty($this::DB_FIELDS)) {
            throw new TDbObjectException('const DB_FIELDS must be defined');
        }

        $this->DB_PRIMARY_KEY = $this::DB_PRIMARY_KEY ?: $this::DB_TABLE . '_id';
        $this->DB_FIELDS = $this::DB_FIELDS;

        if (!in_array($this->DB_PRIMARY_KEY, $this::DB_FIELDS)) {
            $this->DB_FIELDS[] = $this->DB_PRIMARY_KEY;
        }

        $this->_fill($id, $data);
    }

    public function toArray($fieldsFilter = null)
    {
        $data = array();

        foreach (array_merge($this::DB_FIELDS, $this::VR_FIELDS) as $field) {
            $fieldOut = $field;

            if (is_array($fieldsFilter) && array_key_exists($field, $fieldsFilter)) {
                if (!$fieldsFilter[$field]) {
                    continue;
                }
                if (is_string($fieldsFilter[$field])) {
                    $fieldOut = $fieldsFilter[$field];
                }
            }

            $data[$fieldOut] = $this->$field;
        }

        return $data;
    }

    private static function _db()
    {
        static $db;

        $class = get_called_class();

        $db = $db ? $db : new TDatabase($class::DB_CONNECTION_NAME);

        return $db;
    }

    private static function _build_where($where, &$params)
    {
        $str = self::_build_where2($where, $params);

        return $str ? ' WHERE ' . $str : '';
    }

    private static function _build_where2($where, array &$params = array(), $level = 0)
    {
        $ors = array();
        $ands = array();

        foreach ($where as $k => $v) {
            if (is_array($v)) {
                if (empty($v)) {
                    continue;
                }

                $ors[] = self::_build_where2($v, $params, $level++);
            } else {
                $ands[] = '`' . $k . '` = :' . $k . $level;
                $params[$k . $level] = $v;
            }
        }

        if (!empty($ands)) {
            return '(' . implode(' AND ', $ands) . (!empty($ors) ? ' AND (' . implode(' OR ', $ors) . ')' : '') . ')';
        } else if (!empty($ors)) {
            return '(' . implode(' OR ', $ors) . (!empty($ands) ? ' OR (' . implode(' AND ', $ands) . ')' : '') . ')';
        }

        return '';
    }

    private static function _build_order(array $orderBy)
    {
        $ret = array();

        foreach ($orderBy as $field => $order) {
            $ret[] = '`' . $field . '` ' . $order;
        }

        return !empty($ret) ? ' ORDER BY ' . implode(', ', $ret) : '';
    }

    public static function collection(array $where = null, array $orderBy = array(), ?int $limit = null)
    {
        $class = get_called_class();
        $ref = new ReflectionClass($class);
        $consts = $ref->getConstants();

        if (!isset($consts['DB_TABLE'])) {
            throw new TDbObjectException('const DB_TABLE must be defined');
        }

        $pk = $consts['DB_TABLE'] . '_id';

        $params = array();

        $whereStr = $where ? self::_build_where($where, $params) : '';
        $limitStr = $limit !== null ? ' LIMIT ' . $limit : '';

        $orderBy = self::_build_order($orderBy);

        return new TDbObjectCollection(
            $where === null || ($where && $whereStr)
                ? self::_db()->Query('SELECT count(`' . $pk . '`) AS `count` FROM `' . $consts['DB_TABLE'] . '`' . $whereStr . $limitStr, $params)[0]['count']
                : 0,
            $where === null || ($where && $whereStr)
                ? self::_db()->Query('SELECT * FROM `' . $consts['DB_TABLE'] . '`' . $whereStr . $orderBy . $limitStr, $params)
                : new TDatabaseQueryResult,
            $class,
            $consts['DB_TABLE'],
            $pk
        );
    }

    private function _fill($id, $data)
    {
        if ($id) {
            $data = self::_db()->Query('SELECT * FROM `' . $this::DB_TABLE . '` WHERE `' . $this->DB_PRIMARY_KEY . '` = ? LIMIT 1', array($id))[0];
        }

        foreach ($data as $k => $v) {
            if ($this->_isDbField($k)) {
                $this->_db_fields[$k] = $this->_fromDb($k, $v);
            } else if ($this->_isVrField($k)) {
                $this->_vr_fields[$k] = $v;
            }
        }
    }

    private function _isDbField($field)
    {
        return in_array($field, $this->DB_FIELDS) || isset($this->DB_FIELDS[$field]);
    }

    private function _isVrField($field)
    {
        return in_array($field, $this::VR_FIELDS) || isset($this::VR_FIELDS[$field]);
    }

    private function _default($field)
    {
        if ($this->_isDbField($field) && isset($this->DB_FIELDS[$field])) {
            return $this->DB_FIELDS[$field];
        }

        if ($this->_isVrField($field) && isset($this::VR_FIELDS[$field])) {
            return $this::VR_FIELDS[$field];
        }

        return null;
    }

    public function __set($field, $value)
    {
        if ($field === $this->DB_PRIMARY_KEY) {
            throw new TException('Primary key cannot be set: ' . $this->DB_PRIMARY_KEY);
        }

        if ($this->_isDbField($field)) {
            if (!isset($this->_db_fields[$field]) || $this->_db_fields[$field] != $value) {
                $this->_db_fields[$field] = $value;

                if (!in_array($field, $this->_dirty)) {
                    $this->_dirty[] = $field;
                }
            }
        } else if ($this->_isVrField($field)) {
            if (!isset($this->_vr_fields[$field]) || $this->_vr_fields[$field] != $value) {
                $this->_vr_fields[$field] = $value;

                if (!in_array($field, $this->_dirty)) {
                    $this->_dirty[] = $field;
                }
            }
        } else {
            throw new TDbObjectException('Could not set unknown field: ' . $field);
        }
    }

    public function __get($field)
    {
        if ($this->_isDbField($field)) {
            return isset($this->_db_fields[$field]) ? $this->_db_fields[$field] : $this->_default($field);
        } else if ($this->_isVrField($field)) {
            if (!isset($this->_vr_fields[$field])) {
                $this->_vr_fields[$field] = $this->_fromVr($field);
            }

            return $this->_vr_fields[$field];
        } else {
            throw new TDbObjectException('Could not read from unknown field: ' . $field);
        }
    }

    public function __isset($field)
    {
        return isset($this->_db_fields[$field]) || isset($this->_vr_fields[$field]);
    }

    private function _fromDb($field, $value)
    {
        $method = $field . '_fromDb';

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    private function _toDb($field, $value)
    {
        $method = $field . '_toDb';

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    private function _fromVr($field)
    {
        $method = 'get_' . $field;

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new TDbObjectException('virtual field getter undefined: ' . $method);
    }

    private function _toVr($field, $value)
    {
        $method = 'set_' . $field;

        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        return $value;
    }

    protected function primary_key()
    {
        return $this->DB_PRIMARY_KEY;
    }

    public function id()
    {
        return $this->{$this->DB_PRIMARY_KEY};
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetUnset($offset): void
    {
        throw new TException('Could not delete db object field: ' . $offset);
    }

    public function count(): int
    {
        return count($this->_db_fields);
    }


    public function next($orderField)
    {
        if (!$this->_isDbField($orderField)) {
            throw new TException('Unknown field: ' . $orderField);
        }

        $result = $this->_db()->Query('
            WITH cte_asc AS (SELECT * FROM `' . $this::DB_TABLE . '` ORDER BY `' . $orderField . '` ASC, `' . $this->DB_PRIMARY_KEY . '` ASC),
                 cte_r AS (SELECT `' . $this->DB_PRIMARY_KEY . '`, `' . $orderField . '` FROM `' . $this::DB_TABLE . '` WHERE `' . $this->DB_PRIMARY_KEY . '` = ?)
            SELECT * FROM cte_asc
            WHERE `' . $orderField . '` > (SELECT `' . $orderField . '` FROM cte_r) OR `' . $orderField . '` = (SELECT `' . $orderField . '` FROM cte_r) AND `' . $this->DB_PRIMARY_KEY . '` > (SELECT `' . $this->DB_PRIMARY_KEY . '` FROM cte_r)
            LIMIT 1', array($this->id()));

        return count($result) == 1 ? new $this(null, $result[0]) : null;
    }

    public function previous($orderField)
    {
        if (!$this->_isDbField($orderField)) {
            throw new TException('Unknown field: ' . $orderField);
        }

        $result = $this->_db()->Query('
            WITH cte_desc AS (SELECT * FROM `' . $this::DB_TABLE . '` ORDER BY `' . $orderField . '` DESC, `' . $this->DB_PRIMARY_KEY . '` DESC),
                cte_r AS (SELECT `' . $this->DB_PRIMARY_KEY . '`, `' . $orderField . '` FROM `' . $this::DB_TABLE . '` WHERE ' . $this->DB_PRIMARY_KEY . ' = ?)
            SELECT * FROM cte_desc
            WHERE `' . $orderField . '` < (SELECT `' . $orderField . '` FROM cte_r) OR `' . $orderField . '` = (SELECT `' . $orderField . '` FROM cte_r) AND `' . $this->DB_PRIMARY_KEY . '` < (SELECT ' . $this->DB_PRIMARY_KEY . ' FROM cte_r)
            LIMIT 1;
        ', array($this->id()));

        return count($result) == 1 ? new $this(null, $result[0]) : null;
    }


    public function delete()
    {
        $id = $this->{$this->DB_PRIMARY_KEY};

        if ($id) {
            $this->_db()->Query('DELETE FROM `' . $this::DB_TABLE . '` WHERE `' . $this->DB_PRIMARY_KEY . '` = ?', array($id));
        }
    }

    public function save()
    {
        if (empty($this->_dirty)) {
            return;
        }

        $fields = array();
        $values = array();
        $query = null;

        if ($this->{$this->DB_PRIMARY_KEY}) {
            $values[$this->DB_PRIMARY_KEY] = $this->{$this->DB_PRIMARY_KEY};

            foreach ($this->_dirty as $field) {
                if ($this->_isDbField($field)) {
                    $fields[] = '`' . $field . '` = :' . $field;
                    $values[$field] = $this->_toDb($field, $this->_db_fields[$field]);
                }
            }

            if (!empty($fields)) {
                self::_db()->Query('UPDATE `' . $this::DB_TABLE . '` SET ' . implode(', ', $fields) . ' WHERE `' . $this->DB_PRIMARY_KEY . '` = :' . $this->DB_PRIMARY_KEY . ' LIMIT 1', $values);
            }
        } else {
            $values = array();

            foreach ($this->_dirty as $field) {
                if ($this->_isDbField($field)) {
                    $fields[$field] = ':' . $field;
                    $values[$field] = $this->_toDb($field, $this->_db_fields[$field]);
                }
            }

            if (!empty($fields)) {
                self::_db()->Query('INSERT INTO `' . $this::DB_TABLE . '` (`' . implode('`, `', array_keys($fields)) . '`) VALUES (' . implode(', ', $fields) . ')', $values);
                $this->_db_fields[$this->DB_PRIMARY_KEY] = $this->_fromDb($this->DB_PRIMARY_KEY, self::_db()->LastInsertId());
            }
        }

        foreach ($this->_dirty as $field) {
            if ($this->_isVrField($field)) {
                $method = 'save_' . $field;

                if (method_exists($this, $method)) {
                    $this->$method($this->_vr_fields[$field]);
                }
            }
        }
    }
}
