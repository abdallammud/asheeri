<?php
require('db.php');
class Model {
    protected $table;
    protected $primaryKey;
    protected $db;

    public function __construct($table, $primaryKey = 'id') {
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->db = $GLOBALS['conn']; // Use the existing connection from the global variable
    }

    public function read_all($limit = null, $orderBy = null) {
        $query = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $query .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $query .= " LIMIT $limit";
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $this->fetchAll($stmt);
    }

    public function read($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $this->fetch($stmt);
    }

    public function query($sql, $params = [], $types = '') {
        $stmt = $this->db->prepare($sql);
        
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $this->fetchAll($stmt);
    }

    public function create($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $stmt->bind_param(str_repeat('s', count($data)), ...array_values($data)); // Assuming all values are strings
        return $stmt->execute();
    }

    public function update($id, $data) {
        $set = "";
        $types = '';
        $values = [];

        foreach ($data as $key => $value) {
            $set .= "$key = ?, ";
            $types .= 's'; // Assuming all values are strings
            $values[] = $value;
        }
        
        $set = rtrim($set, ', ');
        $values[] = $id;
        $types .= 'i'; // Assuming ID is an integer

        $stmt = $this->db->prepare("UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?");
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function where($conditions, $params = [], $types = '') {
        $conditionString = implode(" AND ", array_map(function($key) {
            return "$key = ?";
        }, array_keys($conditions)));

        $sql = "SELECT * FROM {$this->table} WHERE $conditionString";
        $stmt = $this->db->prepare($sql);
        
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $this->fetchAll($stmt);
    }

    protected function fetchAll($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    protected function fetch($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
