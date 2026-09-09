<?php

$hostname = "is3-dev.ict.ru.ac.za";
$user ="G23K6015";
$password = "KinLuc24!";
$dbname = "civicnexus";

$conn = new mysqli($hostname,$user,$password,$dbname) or
die("Database connection failed". $conn->connect_error);
Echo "Database Connection Sucessfully Established"
?>

