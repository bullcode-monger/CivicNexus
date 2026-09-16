<?php

session_start();
require 'dbconnection.php';


if (!isset($_SESSION['UserID'])) {
    die("You must be logged in to report a fault.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../report_fault.php");
    exit();
}

$userID      = $_SESSION['UserID'];
$category    = trim($_POST['category']    ?? '');
$description = trim($_POST['description'] ?? '');
$wardID      = trim($_POST['ward']        ?? '');
$location    = trim($_POST['address']     ?? '');


if ($category === '' || $description === '' || $wardID === '' || $location === '') {
    die("Please complete all required fields.");
}

if (!filter_var($wardID, FILTER_VALIDATE_INT)) {
    die("Invalid Ward selected.");
}

$wardID = (int)$wardID;

$wardSql  = "SELECT WardID FROM ward WHERE WardID = ?";
$wardStmt = $conn->prepare($wardSql);
$wardStmt->bind_param("i", $wardID);
$wardStmt->execute();
$wardResult = $wardStmt->get_result();
if ($wardResult->num_rows === 0) {
    die("The selected Ward does not exist.");
}
$wardStmt->close();

// Map the form's category word to the ServiceID in the database
$serviceMap = [
    'water'       => 1,
    'electricity' => 2,
    'roads'       => 3,
    'sanitation'  => 4,
    'fire'        => 5,
    'animal'      => 6
];
if (!array_key_exists($category, $serviceMap)) {
    die("Invalid fault category.");
}
$serviceID  = $serviceMap[$category];
$priorityID = 3;                                   // Default medium priority
$title      = ucfirst($category) . " Issue";       // Auto-generated title

$sql  = "INSERT INTO faulticket
         (UserID, WardID, ServiceID, PriorityID, Title, Description, StreetAddress, DateSubmitted, Status)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Open')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiisss", $userID, $wardID, $serviceID, $priorityID, $title, $description, $location);

if (!$stmt->execute()) {
    die("Error submitting ticket: " . $stmt->error);
}

$ticketID = $stmt->insert_id;
$stmt->close();

$historySQL  = "INSERT INTO faultticketstatus
                (TicketID, Status, UpdatedBy, StatusDate, Notes)
                VALUES (?, 'submitted', ?, NOW(), 'Ticket created by community member.')";
$historyStmt = $conn->prepare($historySQL);
$historyStmt->bind_param("ii", $ticketID, $userID);
$historyStmt->execute();
$historyStmt->close();

// Send a notification to the user confirming the submission
$notifTitle   = "Fault Report Submitted";
$notifMessage = "Fault ticket #" . $ticketID . " has been successfully submitted. The municipality will review your report.";

$notifSQL  = "INSERT INTO notifications (UserID, Title, Message, DateSent) VALUES (?, ?, ?, NOW())";
$notifStmt = $conn->prepare($notifSQL);
$notifStmt->bind_param("iss", $userID, $notifTitle, $notifMessage);
$notifStmt->execute();
$notifStmt->close();

// Handling the optional uploaded image
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName    = $_FILES['image']['name'];
    $fileSize    = $_FILES['image']['size'];

    // Allowed files
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    $fileExtension     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Confirm the file is actually an image, not a renamed file
    $imageInfo = @getimagesize($fileTmpPath);

    if (in_array($fileExtension, $allowedExtensions) && $imageInfo !== false) {

        // Limit the file to 5 MB
        if ($fileSize <= 5 * 1024 * 1024) {

            // Give each file a unique name so it never overwrites another
            $newFileName = "ticket_" . $ticketID . "_" . time() . "." . $fileExtension;

            // Two paths: one on disk, one for the browser to read
            $physicalPath = "../uploads/" . $newFileName;
            $webPath      = "uploads/"    . $newFileName;

            if (move_uploaded_file($fileTmpPath, $physicalPath)) {
                $photoSQL  = "INSERT INTO photograph (TicketID, FileName, FilePath, UploadDate)
                              VALUES (?, ?, ?, NOW())";
                $photoStmt = $conn->prepare($photoSQL);
                $photoStmt->bind_param("iss", $ticketID, $newFileName, $webPath);
                $photoStmt->execute();
                $photoStmt->close();
            }
        }
    }
}

// When verything is done, the user is sent to view their fault ticket page 
$conn->close();
header("Location: ../my_faults.php");
exit();
?>