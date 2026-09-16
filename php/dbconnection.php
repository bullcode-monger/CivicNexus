<?php

define("SERVERNAME", "is3-dev.ict.ru.ac.za", );
define("USERNAME", "G24M0576");
define("PASSWORD" , "MapPab24!");
define("DATABASE", "civicnexus");

$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DATABASE);

if ($conn->connect_error) {
    die("Connection to server and database failed: " . $conn->connect_error);
}
?>