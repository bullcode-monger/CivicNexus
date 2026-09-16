<?php

session_start();

require 'dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("You must be logged in.");
}

$userID = $_SESSION['UserID'];

$sql  = "DELETE FROM notifications WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->close();

$conn->close();
header("Location: ../notifications.php");
exit();
?>