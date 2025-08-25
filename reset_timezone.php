<?php
// Reset timezone to Philippines
session_start();

// Clear session timezone
unset($_SESSION['user_timezone']);

// Clear timezone cookie
setcookie('user_timezone', '', time() - 3600, '/');

// Redirect back to main page
header('Location: index.php');
exit();
?>