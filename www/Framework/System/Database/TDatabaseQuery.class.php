<?php
namespace System\Database;

use Exception;
use System\TException;

class TDatabaseQuery {
	protected $_query;
	protected $_params = array();
	public static $Specials = array(array("'",'\\'),array("''",'\\\\'));
	protected static $_cache = array();

	public function __construct($query = '',array $params = array())
	{
		$this->_query = $query;

		if(count($params) > 0)
		{
			$this->bindParams($params);
		}
	}

	public function __set($name, $value)
	{
		$this->bindParam($name, $value);
	}

	public function __get($name)
	{
		throw new TException('Read is not allowed');
	}

	public function bindParams(array $params)
	{
		$this->_params = $params;
	}

	public function bindParam($name, $value)
	{
		$this->_params[$name] = $value;
	}

	public function union($query)
	{
		if(is_string($query))
		{
			if($this->_query != '')$this->_query .= ' UNION '.$query;
			else $this->_query = $query;
		}
		else if($query instanceOf TDatabaseQuery)
		{
			if($this->_query != '')$this->_query .= ' UNION '.$query->__toString();
			else $this->_query = $query->__toString();
		}
	}

	public function __toString()
	{
		if(empty($this->_params)) return $this->_query;
		$query = $this->_query;
		$param = $this->_params;
		
		$arr = preg_split('/(:[a-zA-Z0-9_]+|\?{1})/Si', $query, -1, PREG_SPLIT_DELIM_CAPTURE);

		if(count($arr) > 1)
		{
			$i = 0;
			$query = '';

			foreach($arr as $k => $part)
			{
				if($k%2 != 0)
				{
					if($part == '?')
					{
						if(!isset($param[$i])) throw new TException('Unknown parameter index: '.$i);
						$v = $param[$i++];
					}
					else
					{
						$var = substr($part,1);
						
						try
						{
							$v = $param[$var];
						}
						catch(Exception $e)
						{
							throw new TException('Unknown parameter: '.$var);
						}
					}

					if($v === null)
					{
						$v = 'NULL';
					}
					else
					{
						if(!is_scalar($v)) throw new TException('Value parameter must be scalar or null, '.gettype($v).' given');

						if(is_string($v)) $v = "'".str_replace(self::$Specials[0],self::$Specials[1],$v)."'";
						else if(is_bool($v)) $v = (int) $v;
					}

					$query .= $v;
				}
				else $query .= $part;
			}
		}

		return $query;
	}
}