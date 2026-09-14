<?php

session_start();

require 'lkdbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
     {die('Invalid request method');}



// if (empty($email) || empty($pword)) {die('Email and Password cannot be empty');}

if (isset($_POST['email']) and isset($_POST['password'])) {
    $email = trim($_POST['email']);
    $pword = trim($_POST['password']);
} else {
    die('Email and Password Required');}


$stmt3 = $conn->prepare("SELECT UserID FROM systemusers WHERE Email = ?");
$stmt3->bind_param("s", $email);
$stmt3->execute();
$result3 = $stmt3->get_result();

$userid = $result3->fetch_assoc()['UserID'];



$action = "LOGIN"; //action
$table ="systemusers"; //TableName
$status1 = "unsuccessful";
$status2 = "successful"; //Stauts
$description1 = "Failed login"; //decription
$description2 = "Successful Login";
$timestamp = date("Y-m-d H:i:s"); //DateTime

//--------------------------------------------Page 2

$sql = "SELECT * FROM systemusers WHERE Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
  $sqlogt3 = "INSERT INTO auditlog (UserID, Action, TableName, Status, Description,
    DateTime)
     VALUES (?,?,?,?,?,?)";
    $stmt1 = $conn->prepare($sqlogt3);
    $stmt1->bind_param("isssss", 
    $userid, 
    $action, 
    $table, 
    $status1, 
    $description1,
    $timestamp);
    $stmt1->execute();

    header ('location: Login.html');
    exit();
} else {

    $row = $result->fetch_assoc();

    if (password_verify($pword, $row['PasswordHash'])) {
      
        session_regenerate_id(true);
      
$sqlogt2 = "INSERT INTO auditlog (UserID, Action, TableName, Status, Description,
    DateTime)
     VALUES (?,?,?,?,?,?)";
    $stmt2 = $conn->prepare($sqlogt2);
    $stmt2->bind_param("isssss",
     $userid, $action,
     $table, $status2, $description2,
    $timestamp);
    $stmt2->execute();
        

       //User Role Display
       //Issue, everyones dash boards have the same name
       
       switch($row['Role']) {

       case "community member":
        echo "comm member";
        echo "<p><a href=\"Loginin.html\"><button>Back to Login</button></a></p>";
        //header ('location: dashboard.html');
        break;

        case "ward councilor":
            echo "ward councilor";
            echo "<p><a href=\"Loginin.html\"><button>Back to Login</button></a></p>";
            //header ('location: dashboard.html');
            break;
        
        case "municipal staff":
 
            echo "<p><a href=\"Loginin.html\"><button>Back to Login</button></a></p>";
            //header ('location: dashboard.html');
            break;
        
        case "administrator":
            echo "<p><a href=\"Loginin.html\"><button>Back to Login</button></a></p>";
            //header ('location: dashboard.html');
            break;

            default:
            echo "<p>Access level not recognised, please contact adminstrator";
            echo "<p><a href=\"Loginin.html\"><button>Back to Login</button></a></p>";
            //header ('location: dashboard.html');
            break;
        }
    } else {
  $sqlogt1 = "INSERT INTO auditlog(UserID, Action, TableName, Status, Description,
    DateTime)
     VALUES (?,?,?,?,?,?)";
    $stmt1 = $conn->prepare($sqlogt1);
    $stmt1->bind_param("isssss", $userid, $action, $table, $status1, $description1,
    $timestamp);
    $stmt1->execute();

        echo "<p>Login failed. Invalid Password.</p>";
        echo "<p><a href=\"signin.html\"><button>Back to Login</button></a></p>";
        exit();
       }

    }
$conn->close();
?>