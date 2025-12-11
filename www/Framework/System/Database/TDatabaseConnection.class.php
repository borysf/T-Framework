<?php
namespace System\Database;

use PDO;
use System\TException;

class TDatabaseConnection {
	private static $_connections = [];
	private static $_instances = [];

	public readonly string $name; 
	public readonly string $database; 
	public readonly string $host; 
	public readonly string $username; 
	public readonly ?string $password;
	public readonly string $charset;
	public readonly int $timeout;
	public readonly bool $persistent;

	private function __construct(string $name, string $database, string $host, string $username, ?string $password, string $charset, int $timeout, bool $persistent) {
		$this->name = $name; 
		$this->database = $database;
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->charset = $charset;
		$this->timeout = $timeout;
		$this->persistent = $persistent;
	}

	public static function define(string $name, string $database, string $host, string $username, ?string $password = null, string $charset = 'utf8', int $timeout = 0, bool $persistent = true) {
		if (isset(self::$_connections[$name])) {
			throw new TException('Connection `'.$name.'` already defined');
		}

		self::$_connections[$name] = new TDatabaseConnection(
			$name,
			$database,
			$host,
			$username,
			$password,
			$charset,
			$timeout,
			$persistent
		);

		return self::$_connections[$name];
	}

	public static function get(string $name) : TDatabaseConnection {
		if (!isset(self::$_connections[$name])) {
			throw new TException('Connection `'.$name.'` does not exist');
		}

		return self::$_connections[$name];
	}

	public static function db(string $name) : PDO {
		if (isset(self::$_instances[$name])) {
			return self::$_instances[$name];
		}
		
		$cfg = self::get($name);

		self::$_instances[$name] = new PDO(
			sprintf('mysql:dbname=%s;host=%s', $cfg->database, $cfg->host),
			$cfg->username,
			$cfg->password,
			[
				PDO::MYSQL_ATTR_FOUND_ROWS => true,
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => $cfg->timeout,
				PDO::ATTR_PERSISTENT => $cfg->persistent
			]
		);

		self::$_instances[$name]->query(new TDatabaseQuery('SET NAMES ?', [$cfg->charset]));

		return self::$_instances[$name];
	}
}
