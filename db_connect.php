<?php
$host = "localhost";
$username = "root";  //Default WAMP username
$password = "";      //Default WAMP password
$database = "wheelsonrent";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>