<?php

$host = "://aivencloud.com";
$username = "avnadmin";
$password = getenv('DB_PASSWORD') ;
$database = "defaultdb";
$port = 10837;

$conn = mysqli($host, $username, $password, $database, $port);

if (!$conn) {
    die("connection failed: " . mysqli_connect_error());
}



?>
