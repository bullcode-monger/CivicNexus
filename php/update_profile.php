<?php

session_start();
require 'dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("You must be logged in.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../profile.php");
    exit();
}

$userID    = $_SESSION['UserID'];
$firstName = trim($_POST['first-name'] ?? '');
$lastName  = trim($_POST['last-name']  ?? '');
$email     = trim($_POST['email']      ?? '');

if ($firstName === '' || $lastName === '' || $email === '') {
    die("Please complete all fields.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}

// Store the user's updated details in the database
$sql  = "UPDATE systemUsers
         SET FirstName = ?, LastName = ?, Email = ?
         WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $firstName, $lastName, $email, $userID);

if (!$stmt->execute()) {
    die("Error updating profile: " . $stmt->error);
}
$stmt->close();

// Update the session with the new first name so the dashboard calls the right thing
$_SESSION['firstName'] = $firstName;

// Send the user back to their profile page
$conn->close();
header("Location: ../profile.php");
exit();
?>