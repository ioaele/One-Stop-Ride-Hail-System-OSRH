<?php 

class Database {
    private $host = "mssql.cs.ucy.ac.cy";
    private $db_name = "eioann09";
    private $username = "eioann09";
    private $password = "CQxPy3nG";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
         
            $this->conn = new PDO(
                "odbc:Driver={ODBC Driver 18 for SQL Server};Server=" . $this->host . ";Database=" . $this->db_name . ";TrustServerCertificate=yes",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
      
            throw new Exception("Connection failed: " . $e->getMessage());
        }
        return $this->conn;
    }
}


function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>