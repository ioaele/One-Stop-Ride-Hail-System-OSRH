<?php
/**
 * PHP Script to Execute the 'RightToBeForgottenRequest' Stored Procedure
 * * This script uses PHP Data Objects (PDO) for secure database interaction.
 * The PDO driver used here is for SQL Server (sqlsrv), matching the syntax
 * of the provided stored procedure.
 */

// Start the session to access session variables (like the logged-in user's ID)
session_start();

// 1. Configuration: Update these database credentials and details
$serverName = "mssql.cs.ucy.ac.cy";
$dbName     = "eioann09";
$dbUser     = "eioann09";
$dbPass     = "CQxPy3nG";
// New: Get the user ID from the session. The fallback (?? 999) has been removed.
// If $_SESSION['user_id'] is not set, this will be null/undefined, and the
// crucial check below will immediately stop the script.
$userIdToDelete = $_SESSION['users_id']; 
$storedProcName = "[eioann09].[RightToBeForgottenRequest]";

// NOTE: You must have the 'pdo_sqlsrv' driver installed and configured in your PHP environment.
// For MySQL, you would change the DSN to: "mysql:host=$serverName;dbname=$dbName"

// Crucial check: Ensure a valid user ID is present before attempting deletion.
if (!is_numeric($userIdToDelete) || $userIdToDelete <= 0) {
    echo "<p style='color: red; font-weight: bold;'>Error: Invalid or missing user ID in session. Cannot proceed with deletion.</p>";
    // Exit the script if the ID is not valid
    exit;
}

try {
    // 2. Establish Connection
    $dsn = "sqlsrv:server=$serverName;database=$dbName";
    $conn = new PDO($dsn, $dbUser, $dbPass);
    
    // Set PDO error mode to exception for proper error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 3. Prepare the Stored Procedure Call
    // Use call syntax for stored procedures and a placeholder (?) for the parameter
    $sql = "{CALL $storedProcName (?)}";
    $stmt = $conn->prepare($sql);

    // 4. Bind Parameters
    // Bind the user ID to the placeholder. Using placeholders is crucial for security (prevents SQL injection).
    $stmt->bindParam(1, $userIdToDelete, PDO::PARAM_INT);

    // 5. Execute the Procedure
    $stmt->execute();

    // 6. Output Result
    // Check how many rows were affected by the DELETE operation inside the procedure
    $rowCount = $stmt->rowCount();

    if ($rowCount > 0) {
        session_unset();
        session_destroy();
        header("Location: login.html");
        exit;
    }

} catch (PDOException $e) {
    // 7. Error Handling
    // Catch PDO exceptions (connection failure, query execution error, etc.)
    echo "<p style='color: red; font-weight: bold;'>Database Error:</p>";
    // In a production environment, avoid showing $e->getMessage() to the user.
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    // Optional: Log the error
    // error_log("DB Error: " . $e->getMessage());

} catch (Exception $e) {
    // Catch other general exceptions
    echo "<p style='color: red;'>An unexpected error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
} finally {
    // 8. Close Connection (optional, as PHP automatically closes it when script finishes)
    $conn = null;
    $stmt = null;
}
?>