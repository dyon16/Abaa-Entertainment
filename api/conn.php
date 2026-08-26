<?php

$host = "mysql-177c6bd3-abaa.g.aivencloud.com";
$port = 11063;
$database = "defaultdb";
$user = "avnadmin";
$password = getenv("DB_PASSWORD");

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log(
        "Database connection failed: " . $e->getMessage()
    );

    die("Database connection failed. Please try again later.");

}
?>
