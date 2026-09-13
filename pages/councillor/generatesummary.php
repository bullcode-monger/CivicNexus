<?php

session_start();

include "dbConnection.php";

// check if the councillor is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['userID']) || !isset($_SESSION['ward'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];
$ward = $_SESSION['ward'];

$message = "";
$error = "";

// generate notice
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $noticeType = trim($_POST['noticeType']);
    $publishDate = $_POST['publishDate'];
    $expiryDate = $_POST['expiryDate'];

    if (empty($title) || empty($noticeType) || empty($publishDate) || empty($expiryDate)) {
        $error = "Please complete all fields.";
    } elseif ($expiryDate < $publishDate) {
        $error = "Expiry date cannot be before the publish date.";
    } else {
        try {
            $conn->begin_transaction();

            // insert notice
            $sql = "INSERT INTO notices (WardID, Tiitle, NouticeType, PublishDate, ExpiryDate)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $ward, $title, $noticeType, $publishDate, $expiryDate);

            if (!$stmt->execute()) {
                throw new Exception("Notice could not be generated.");
            }

            $noticeID = $stmt->insert_id;
            $stmt->close();

            // create successful audit log
            $action = "CREATE";
            $tableName = "notices";
            $status = "successful";
            $description = "Ward " . $ward . " notice '" . $title . "' was generated and published.";

            $auditSQL = "INSERT INTO auditlog
                        (UserID, Action, TableName, RecordID, Status, Description, DateTime)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $auditStmt = $conn->prepare($auditSQL);
            $auditStmt->bind_param(
                "ississ",
                $userID,
                $action,
                $tableName,
                $noticeID,
                $status,
                $description
            );

            if (!$auditStmt->execute()) {
                throw new Exception("Audit log could not be created."); //should execution fail
            }

            $auditStmt->close();

            $conn->commit();

            header("Location: wardsummaries.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();

            // create unsuccessful audit log
            $action = "CREATE";
            $tableName = "notices";
            $recordID = 0;
            $status = "unsuccessful";
            $description = "Unsuccessful attempt to generate a Ward " . $ward . " notice.";

            $auditSQL = "INSERT INTO auditlog
                        (UserID, Action, TableName, RecordID, Status, Description, DateTime)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $auditStmt = $conn->prepare($auditSQL);
            $auditStmt->bind_param(
                "ississ",
                $userID,
                $action,
                $tableName,
                $recordID,
                $status,
                $description
            );
            $auditStmt->execute();
            $auditStmt->close();

            $error = "The notice could not be generated.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="generatesummary.css">
    <title>MakhandaPulse - Generate Ward Summary</title>
</head>

<body>

<header>
    <div id="logo">
        <h1 id="Makhanda">Makhanda</h1>
        <h1 id="Pulse">Pulse</h1>
    </div>

    <div class="profile-section">
        <a href="profile.php" class="profile-icon">
            <i class="fa-regular fa-user"></i>
        </a>
    </div>
</header>

<main class="main-content">

    <div class="summary-container">

        <h2>Generate Ward Summary</h2>

        <p class="description">
            Create and publish a notice for Ward <?php echo htmlspecialchars($ward); ?>.
        </p>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">

            <label for="ward">Ward</label>
            <input type="text"
                   id="ward"
                   value="Ward <?php echo htmlspecialchars($ward); ?>"
                   readonly>

            <label for="title">Notice Title</label>
            <input type="text"
                   id="title"
                   name="title"
                   maxlength="100"
                   placeholder="Enter notice title"
                   required>

            <label for="noticeType">Notice Type</label>
            <select id="noticeType" name="noticeType" required>
                <option value="">Select notice type</option>
                <option value="Water">Water</option>
                <option value="Electricity">Electricity</option>
                <option value="Roads">Roads</option>
                <option value="Sanitation">Sanitation</option>
                <option value="Emergency">Emergency</option>
                <option value="General">General</option>
            </select>

            <label for="publishDate">Publish Date</label>
            <input type="date"
                   id="publishDate"
                   name="publishDate"
                   value="<?php echo date('Y-m-d'); ?>"
                   required>

            
            <label for="expiryDate">Expiry Date</label>
            <input type="date"
                    id="expiryDate"
                    name="expiryDate"
                    value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                    required>



            <div class="form-buttons">
                <a href="wardsummaries.php" class="cancel-button">Cancel</a>
                <button type="submit" class="publish-button">
                    <i class="fa-solid fa-paper-plane"></i>
                    Publish Notice
                </button>
            </div>

        </form>

    </div>

</main>

<footer>
    <p>&copy; 2026 MakhandaPulse. All rights reserved.</p>
</footer>

</body>
</html>



