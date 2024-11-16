<?php

// $serverName = "localhost:3306";
// $username = "root";
// $password = "12345";
// $dbName = "FreelancerWebsite";

// A 
// $serverName = "localhost";
// $username = "root";
// $password = "";
// $dbName = "FreelancerWebsite";

// L 
$serverName = "localhost:3307";
$username = "root";
$password = "1234";
$dbName = "FreelancerWebsite";



try {
    $conn = new PDO("mysql:host=$serverName;dbname=$dbName", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
