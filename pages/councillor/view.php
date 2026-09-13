<?php
session_start();
include "dbConnection.php";

// ensure the councillor is logged in
if (!isset($_SESSION['ward'])) {
    header("Location: login.php");
    exit();
}

// retrieves the councillor's ward
$ward = $_SESSION['ward'];

// retrieves the ticket id from the url
if (!isset($_GET['id'])) {
    echo "No ticket was selected.";
    exit();
}

$ticketID = $_GET['id'];

// retrieves the ticket only if it belongs to the councillor's ward
$sql = "SELECT
            TicketID,
            UserID,
            WardID,
            ServiceID,
            PriorityID,
            Title,
            Description,
            StreetAddress,
            DateSubmitted,
            DateResolved
        FROM faultticket
        WHERE TicketID = ?
        AND WardID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticketID, $ward);
$stmt->execute();

$result = $stmt->get_result();

// checks if ticket exists in the councillor's ward
if ($result->num_rows == 0) {
    echo "You are not authorised to view this ticket.";
    exit();
}

// retrieves ticket information
$ticket = $result->fetch_assoc();

$stmt->close();

// gets the latest status from faultticketstatus
$statusSQL = "SELECT
                  Status,
                  StatusDate,
                  Notes,
                  UpdatedBy
              FROM faultticketstatus
              WHERE TicketID = ?
              ORDER BY StatusHistoryID DESC
              LIMIT 1";

$statusStmt = $conn->prepare($statusSQL);
$statusStmt->bind_param("i", $ticketID);
$statusStmt->execute();

$statusResult = $statusStmt->get_result();

// sets default status
$status = "Open";
$statusDate = "";
$statusNotes = "";
$updatedBy = "";

if ($statusResult->num_rows > 0) {
    $statusData = $statusResult->fetch_assoc();

    $status = $statusData['Status'];
    $statusDate = $statusData['StatusDate'];
    $statusNotes = $statusData['Notes'];
    $updatedBy = $statusData['UpdatedBy'];
}

$statusStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <link rel="stylesheet" href="view.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

<aside class="sidebar">
    <nav class="sidebar-menu">

        <a href="dashboard.php" class="menu-item">
            <span>Dashboard</span>
        </a>

        <a href="reportfault.php" class="menu-item">
            <span>Report Faults</span>
        </a>

        <a href="mytickets.php" class="menu-item">
            <span>My Tickets</span>
        </a>

        <a href="wardfaults.php" class="menu-item active">
            <span>Ward Faults</span>
        </a>

        <a href="wardsummaries.php" class="menu-item">
            <span>Ward Summaries</span>
        </a>

        <a href="notifications.php" class="menu-item">
            <span>Notifications</span>
        </a>

        <a href="logout.php" class="menu-item logout">
            <span>Logout</span>
        </a>

    </nav>
</aside>

<main class="dashboard-content">

    <div class="page-header">

        <div>
            <h2>Ticket Details</h2>
            <p>View the details of a reported municipal fault.</p>
        </div>

        <a href="wardfaults.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Ward Faults
        </a>

    </div>

    <section class="ticket-card">

        <div class="ticket-header">

            <div>
                <span class="ticket-label">Ticket ID</span>

                <h3>
                    #<?php echo htmlspecialchars($ticket['TicketID']); ?>
                </h3>
            </div>

            <span class="status <?php echo strtolower(str_replace(' ', '-', htmlspecialchars($status))); ?>">
                <?php echo htmlspecialchars($status); ?>
            </span>

        </div>

        <div class="ticket-information">

            <div class="information-item">
                <span>Title</span>
                <strong>
                    <?php echo htmlspecialchars($ticket['Title']); ?>
                </strong>
            </div>

            <div class="information-item">
                <span>Date Submitted</span>
                <strong>
                    <?php echo htmlspecialchars($ticket['DateSubmitted']); ?>
                </strong>
            </div>

            <div class="information-item">
                <span>Ward</span>
                <strong>
                    Ward <?php echo htmlspecialchars($ticket['WardID']); ?>
                </strong>
            </div>

            <div class="information-item">
                <span>Location</span>
                <strong>
                    <?php echo htmlspecialchars($ticket['StreetAddress']); ?>
                </strong>
            </div>

            <div class="information-item">
                <span>Service ID</span>
                <strong>
                    <?php echo htmlspecialchars($ticket['ServiceID']); ?>
                </strong>
            </div>

            <div class="information-item">
                <span>Priority</span>
                <strong>
                    <?php echo htmlspecialchars($ticket['PriorityID']); ?>
                </strong>
            </div>

        </div>

        <div class="ticket-section">

            <h3>Fault Description</h3>

            <p>
                <?php echo nl2br(htmlspecialchars($ticket['Description'])); ?>
            </p>

        </div>

        <div class="ticket-section">

            <h3>Ticket Status</h3>

            <div class="status-progress">

                <div class="progress-step
                    <?php
                    if (
                        $status == 'Open' ||
                        $status == 'In Progress' ||
                        $status == 'Resolved'
                    ) {
                        echo 'completed';
                    }
                    ?>">

                    <span>1</span>
                    <p>Open</p>

                </div>

                <div class="progress-line
                    <?php
                    if (
                        $status == 'In Progress' ||
                        $status == 'Resolved'
                    ) {
                        echo 'completed-line';
                    }
                    ?>">
                </div>

                <div class="progress-step
                    <?php
                    if ($status == 'In Progress') {
                        echo 'active';
                    }

                    if ($status == 'Resolved') {
                        echo 'completed';
                    }
                    ?>">

                    <span>2</span>
                    <p>In Progress</p>

                </div>

                <div class="progress-line
                    <?php
                    if ($status == 'Resolved') {
                        echo 'completed-line';
                    }
                    ?>">
                </div>

                <div class="progress-step
                    <?php
                    if ($status == 'Resolved') {
                        echo 'active';
                    }
                    ?>">

                    <span>3</span>
                    <p>Resolved</p>

                </div>

            </div>

        </div>

        <?php if (!empty($statusNotes)) { ?>

            <div class="ticket-section">

                <h3>Latest Update</h3>

                <p>
                    <?php echo nl2br(htmlspecialchars($statusNotes)); ?>
                </p>

                <?php if (!empty($statusDate)) { ?>

                    <small>
                        Updated:
                        <?php echo htmlspecialchars($statusDate); ?>
                    </small>

                <?php } ?>

            </div>

        <?php } ?>

        <?php if (!empty($ticket['DateResolved'])) { ?>

            <div class="ticket-section">
                <h3>Date Resolved</h3>

                <p>
                    <?php echo htmlspecialchars($ticket['DateResolved']); ?>
                </p>
            </div>

        <?php } ?>

        <div class="ticket-actions">

            <a href="update.php?id=<?php echo $ticket['TicketID']; ?>" class="update-btn">
                Update Ticket
            </a>

        </div>

    </section>

</main>

<footer>
    <p>&copy; 2026 MakhandaPulse. All rights reserved.</p>
</footer>

</body>
</html>

