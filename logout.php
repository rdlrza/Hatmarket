<?php
require_once 'includes/common.php';

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Set success message in a temporary cookie
setcookie('logout_message', 'You have been successfully logged out!', time() + 5, '/');

// Redirect to login page
header('Location: ' . getSiteUrl() . '/login.php');
exit();
