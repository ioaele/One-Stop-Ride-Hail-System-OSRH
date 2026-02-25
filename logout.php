<?php
// Start the session if it hasn't been started already. 
// This is necessary to access session variables.
session_start();

// Unset all of the session variables.
// This is a safe way to clear session data without destroying the session cookie immediately.
$_SESSION = array();

// If you want to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect the user back to the login page or the home page.
// In a real application, you would change 'index.html' to your actual landing page.
header("Location: login.html");
exit;
?>