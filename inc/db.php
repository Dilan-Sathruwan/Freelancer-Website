<?php

// $serverName = "localhost:3306";
// $username = "root";
// $password = "1234";
// $dbName = "freelancer";

// A 
// $serverName = "localhost:3307";
// $username = "root";
// $password = "1234";
// $dbName = "freelancer";



try {
    $conn = new PDO("mysql:host=$serverName;dbname=$dbName", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
