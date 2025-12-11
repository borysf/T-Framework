<?php
namespace System\Utils\Js;

class TJsStatement {
    protected $_statement;

    public function __construct($statement) {
        $this->_statement = $statement;
    }

    public function __toString() {
        return ''.$this->_statement;
    }
}