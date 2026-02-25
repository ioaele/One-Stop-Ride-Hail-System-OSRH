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
            // Try SQL Server PDO driver first (recommended)
            $this->conn = new PDO(
                "sqlsrv:Server=" . $this->host . ";Database=" . $this->db_name . ";TrustServerCertificate=yes",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Connection successful - don't echo anything!
            
        } catch(PDOException $e) {
            // If sqlsrv fails, try ODBC as fallback
            try {
                $this->conn = new PDO(
                    "odbc:Driver={ODBC Driver 18 for SQL Server};Server=" . $this->host . ";Database=" . $this->db_name . ";TrustServerCertificate=yes",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Connection successful - don't echo anything!
                
            } catch(PDOException $e2) {
                // Log errors to a file instead of echoing them
                error_log("Database connection failed: " . $e2->getMessage());
                // Don't echo errors when returning JSON!
            }
        }
        return $this->conn;
    }
    
    public function closeConnection() {
        $this->conn = null;
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