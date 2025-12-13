<?php

class Database {
    private $host = "127.0.0.1";
    private $username = "root";
    private $password = "";
    private $dbname = "course_withdrawal_system";
    private $charset = "utf8mb4";

    protected $conn;

    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $this->conn;

        } catch (PDOException $e) {
            // EError message for debugging
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
}

// For testing purposes
// $db = new Database();
// $conn = $db->connect();
// var_dump($conn);
?>
