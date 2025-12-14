<?php
/**
 * Database Configuration
 * 
 * ⚠️ FOR PRODUCTION (Hostinger):
 * Update these values with your Hostinger database credentials from hPanel
 */

class Database
{
    // UPDATE THESE FOR PRODUCTION
    private $host = "localhost";           // Usually 'localhost' on Hostinger
    private $username = "u194078580_admin";             // Your Hostinger DB username (e.g., u123456789_admin)
    private $password = "Mcpemaster@1";                 // Your Hostinger DB password
    private $dbname = "u194078580_course_withdr";  // Your Hostinger DB name (e.g., u123456789_course)
    private $charset = "utf8mb4";

    protected $conn;

    public function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $this->conn;

        } catch (PDOException $e) {
            // Log error for debugging (check PHP error log)
            error_log("Database Connection Failed: " . $e->getMessage());
            // Show generic message to users
            die("Unable to connect to the database. Please try again later or contact the administrator.");
        }
    }
}

// For testing purposes
// $db = new Database();
// $conn = $db->connect();
// var_dump($conn);
?>