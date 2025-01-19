<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "voidx";

// Create connection
$con = mysqli_connect($host, $user, $password, $dbname);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
