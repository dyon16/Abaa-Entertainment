<?php
$host = "mysql-177c6bd3-abaa.g.aivencloud.com";
$user = "avnadmin";
$database = "defaultdb";
$port = 11063;
$password = getenv('DB_PASSWORD'); 

try {
    // 1. Establish the native PDO cloud connection required by Vercel
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Assign an active variable token to prevent empty pointer crashes
    $conn = $pdo;
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Fallback translation functions to handle your page query lines automatically
if (!function_exists('mysqli_connect')) {
    function mysqli_connect($host, $user, $password, $database, $port) {
        global $pdo;
        return $pdo;
    }
    
    function mysqli_query($conn, $query) {
        try {
            return $conn->query($query);
        } catch (Exception $e) {
            return false;
        }
    }
    
    function mysqli_fetch_assoc($result) {
        if (!$result) return false;
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    
    function mysqli_error($conn) {
        return "Database execution exception handled.";
    }
}
?>
