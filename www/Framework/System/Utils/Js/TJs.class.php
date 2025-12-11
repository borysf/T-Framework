<?php
namespace System\Utils\Js;

class TJs {
    private $_statements = array();

    public function __construct(array $statements = array(), $renderTags = true) {
        $this->_statements = $statements;
        $this->_renderTags = $renderTags;
    }

    public function write(TJsStatement $statement) {
        $this->_statements[] = $statement;
    }

    public function __toString() {
        return $this->scriptBegin()."\n".implode("\n", $this->_statements)."\n".$this->scriptEnd();
    }

    public function scriptBegin() {
        if ($this->_renderTags) {
            return '<script type="text/javascript">';
        }
    }

    public function scriptEnd() {
        if ($this->_renderTags) {
            return '</script>';
        }
    }

    public static function var($name, $value) {
        return new TJsStatement('var '.$name.' = '.self::__toJS($value).';');
    }

    public static function statement($statement) {
        return new TJsStatement($statement);
    }

    public static function call($functionName, array $args) {
        return new TJsStatement($functionName.'('.implode(', ',  array_map(function($v) { return self::__toJS($v); }, $args)).')');
    }

    private static function __toJS($v) {
        if (is_object($v) && $v instanceof TJsStatement) {
            return $v->__toString();
        }

        if (is_string($v) || (is_object($v) && method_exists($v, '__toString'))) {
            return "'".htmlspecialchars(str_replace("'", "\\'", ''.$v))."'";
        }

        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }

        if (is_null($v)) {
            return 'null';
        }

        if (is_object($v) || is_array($v)) {
            return json_encode($v);
        }

        if (is_numeric($v)) {
            return $v;
        }

        throw new TJsException('Could not convert value of type '.gettype($v).' to JS');
    }
}