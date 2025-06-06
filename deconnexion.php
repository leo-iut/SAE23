<?php
session_start(); // Start or resume the session to access its variables.
session_unset(); // Remove all session variables from the current session.
session_destroy(); // Destroy the entire session, deleting the session file on the server.
header("Location: connexion.php"); // Redirect the user to the login page ('connexion.php').
exit(); // Stop script execution to ensure the redirect happens immediately.
?>
