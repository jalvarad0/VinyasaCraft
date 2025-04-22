<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$servername = "localhost";
$dbUsername = "udvnhgd3sliun";
$dbPassword = "32w$)kA$(1x6";
$dbName   = "db9jgi4qvgpxkt";


$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// helper fcn: redirect if not logged in
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>