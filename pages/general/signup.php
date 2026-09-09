<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | MakhandaPulse</title>
    <link rel="stylesheet" href="styleslk.css">
</head>
<body>
<header>
    <!-- Self made logo-->
    <div id="logo">
        <h1 id="Makhanda">Makhanda</h1>
        <h1 id="Pulse">Pulse</h1>
    </div>


    <!--Navigation links-->
    <nav id="nav-links">
        <a href="homegen.html">Home</a>
        <a href="aboutus.html">About Us</a>
        <a href="services.html">Services</a>
        <a href="notices.html">Ward Notices</a>
        <a href="contactus.html">Contact</a>
    </nav>

    <!--Profile and Logout-->
    <div id="profile-actions">
        <a href="">My Profile</a>
        <a href="">Logout</a>
    </div>
</header>
<main><?php

include "lkdbconnect.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

$role = isset($_POST['role']) ? $_POST['role'] : '';
$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
$lastname = isset($_POST['surname']) ? trim($_POST['surname']) : '';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
$ward = isset($_POST['ward']) ? $_POST['ward'] : '';
$address =isset($_POST['address']) ? $_POST['address'] : '';

$pword = isset($_POST['pword']) ? $_POST['pword'] : '';
$hash_pword =  !empty($pword) ? password_hash($pword, PASSWORD_DEFAULT) : '';

$status  = "pending";
$date = date("Y-m-d H:i:s");
$active = "1";


$stmt = $conn->prepare("INSERT INTO systemusers (Role, FirstName, LastName, Email, PasswordHash, Address,
DateRegistered ,AccountStatus, Active)
                        VALUES(?,?,?,?,?,?,?,?,?)");

$stmt->bind_param("ssssssssi",
$role,
$firstname,
$lastname,
$email,
$hash_pword,
$address,
$date,
$status,
$active);

if($stmt->execute()) {
Echo "Succesfull Sign Up";
$stmt->close();
$conn->close();
} else {
    die("Failed to registar");
}
}?>
<br>
<a href="signup.html">Sign Up</a>
<a href=signin.html>Sign In</a>
</main>
    <footer>
        <nav>
            <a href="aboutus.html">About Us</a>
            <a href="contactus.html">Contact</a>
            <a href="">Help</a>
            <a href="">Terms and Conditions</a>
        </nav>
        <p>&copy; 2026 MakhandaPulse. All Rights Reserved</p>
    </footer>

</body>
</html>






