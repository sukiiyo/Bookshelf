<?php
// 1. Start the session to access existing session data
session_start();

// 2. Unset all session variables
$_SESSION = array();

// 3. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destroy the session on the server
session_destroy();

// 5. Redirect to the login page and stop script execution
header("Location: login.php");
exit();
?>