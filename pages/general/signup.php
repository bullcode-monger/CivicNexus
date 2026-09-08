<?php

include "lkdbconnect.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

$role = isset($_POST['role']) ? $_POST['role'] : '';
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
$ward = isset($_POST['ward']) ? $_POST['ward'] : '';
$adress =isset($_POST['adress']) ? $_POST['adress'] : '';

$pword = isset($_POST['pword']) ? $_POST['pword'] : '';
$hash_pword =  !empty($pword) ? password_hash($pword, PASSWORD_DEFAULT) : '';

$status  = if ($role = 'community member') {
    $status = 'active'
} else { $status = 'pending'};

$stmt = $conn->prepare("INSERT INTO systemusers (Role, FirstName, LastName, Email, PasswordHash, Adress, AccountStatus, Ward)
                        VALUES(?,?,?,?,?,?,?,?)");

$stmt->bind_param("sssssssi",$role,$firstname,$lastname,$email,$hash_pword,$adress,$status,$ward);

if($stmt->execute()) {
Echo "Succesfull Sign Up";
$stmt->close();
$conn->close();
} else {
    die("Failed to registar");
}

?>



