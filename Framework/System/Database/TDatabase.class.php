<?php
namespace System\Database;

use PDO;

class TDatabase {
    public function __construct(string $connectionName) {
        $this->db = TDatabaseConnection::db($connectionName);
    }

    public function query(string $query, array $params = []) : TDatabaseQueryResult {
        $q = $this->db->query(new TDatabaseQuery($query, $params), PDO::FETCH_ASSOC);
        
        return new TDatabaseQueryResult($q);
    }

    public function lastInsertId() : int {
        return $this->db->lastInsertId();
    }
}