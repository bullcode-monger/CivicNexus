<?php

session_start();
require 'php/dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("Please run session_sim.php first to log in.");
}

$userID   = $_SESSION['UserID'];
$ticketID = $_GET['id'] ?? 0;

// Fetch the ticket, only if it belongs to this user
$sql = "SELECT t.TicketID, t.Title, t.Description, t.StreetAddress,
               t.DateSubmitted, t.DateResolved, t.Status,
               s.ServiceName, w.WardName, p.PriorityName
        FROM faultTicket t
        JOIN municipalService s ON t.ServiceID  = s.ServiceID
        JOIN ward w ON t.WardID     = w.WardID
        JOIN priority p ON t.PriorityID = p.PriorityID
        WHERE t.TicketID = ? AND t.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $ticketID, $userID);
$stmt->execute();
$result = $stmt->get_result();

// Stop if the ticket does not exist or belongs to someone else
if ($result->num_rows === 0) {
    die("Ticket not found.");
}

$ticket = $result->fetch_assoc();

// Fetch the status history (oldest first)
$historySql  = "SELECT Status, StatusDate, Notes
                FROM faultTicketStatus
                WHERE TicketID = ?
                ORDER BY StatusDate ASC";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param("i", $ticketID);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

// Fetch the photos uploaded for this ticket
$photoSql  = "SELECT PhotoID, FileName, FilePath
              FROM photograph
              WHERE TicketID = ?";
$photoStmt = $conn->prepare($photoSql);
$photoStmt->bind_param("i", $ticketID);
$photoStmt->execute();
$photoResult = $photoStmt->get_result();

require 'php/header.php';
?>

<!--_____________________________________
     TRACK FAULT PAGE SPECIFIC STYLES
__________________________________________-->
<style>
    /* Main ticket card */
    .ticket-card {
        width: 100%;
        max-width: 46.5625rem;
        margin: 0 auto;
        padding: 1.5rem 1.6875rem;
        box-sizing: border-box;
        background-color: #E9E4E0;
        border: 0.0625rem solid #111111;
        border-radius: 4.0625rem;
    }

    .ticket-card h1 {
        margin: 0 0 1.25rem 0.8125rem;
        font-size: 1.5rem;
        color: #193044;
        text-decoration: underline;
    }

    /* Top bar with the ticket ID and the status dot */
    .ticket-status {
        width: 100%;
        height: 3.1875rem;
        box-sizing: border-box;
        padding: 0 1.3125rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #FFFFFF;
        border: 0.0625rem solid #555555;
        border-radius: 0.5rem;
    }

    .ticket-status strong {
        font-size: 1.25rem;
    }

    .status {
        display: flex;
        align-items: center;
        gap: 0.5625rem;
        font-size: 1.25rem;
    }

    .status-dot {
        width: 1.4375rem;
        height: 1.4375rem;
        border-radius: 50%;
        border: 0.0625rem solid #111111;
        box-sizing: border-box;
    }

    /* Colour of the dot depends on the ticket's current status */
    .status-dot.open        { background-color: red; }
    .status-dot.in-progress { background-color: orange; }
    .status-dot.resolved,
    .status-dot.closed      { background-color: #00b050; }

    /* Two-column grid: description on the left, meta on the right */
    .ticket-content {
        display: grid;
        grid-template-columns: 1fr 11.3125rem;
        gap: 2.5rem;
        margin-top: 1.5rem;
    }

    /* Left panel: the fault description and uploads */
    .ticket-description {
        min-height: 18.875rem;
        box-sizing: border-box;
        padding: 0.875rem 1.75rem;
        background-color: #FFFFFF;
        border: 0.0625rem solid #555555;
        border-radius: 2.875rem;
    }

    .ticket-description h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 400;
    }

    /* Thin horizontal separator */
    .line {
        width: 100%;
        height: 0.0625rem;
        margin-top: 0.75rem;
        background-color: #777777;
    }

    .details-title {
        margin: 1.125rem 0 0 0;
        font-size: 0.875rem;
    }

    .details-area {
        margin-top: 0.5rem;
        min-height: 7rem;
        font-size: 0.875rem;
        color: #000000;
    }

    .uploads-title {
        margin: 0.1875rem 0 0.3125rem 0;
        font-size: 0.875rem;
    }

    /* Thumbnails of the uploaded images */
    .uploads {
        display: flex;
        gap: 0.625rem;
        margin: 0.3125rem 0;
        flex-wrap: wrap;
    }

    /* Thumbnails of the uploaded images */
    .upload-image {
        width: 4.75rem;
        height: 3.75rem;
        box-sizing: border-box;
        border: 0.0625rem solid #555555;
        background-color: #F4F4F4;
        overflow: hidden;
        display: block;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .upload-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .upload-image:hover {
    transform:scale(1.05);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
    }
    
    /* Right panel: log date and log address */
    .ticket-meta {
        box-sizing: border-box;
        padding: 0.875rem 1.0625rem;
        background-color: #FFFFFF;
        border: 0.0625rem solid #555555;
        border-radius: 1.5625rem;
    }

    .meta-item h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 400;
        text-decoration: underline;
        text-align: center;
    }

    .meta-item p {
        text-align: center;
        margin-top: 0.625rem;
        font-size: 0.875rem;
        color: #000000;
    }

    .meta-item.address {
        margin-top: 3.1875rem;

    }

    /* Status History section under the card */
    .status-history {
        max-width: 46.5625rem;
        margin: 2.5rem auto 0 auto;
    }

    .status-history h2 {
        font-size: 1.8rem;
        color: #172A39;
        margin-bottom: 1.25rem;
    }

    .status-history-item {
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-left: 0.3125rem solid #172A39;
        border-radius: 0.625rem;
        padding: 0.9375rem 1.25rem;
        margin-bottom: 0.75rem;
    }

    .status-history-item h3 {
        margin: 0 0 0.375rem 0;
        font-size: 1.1rem;
        color: #172A39;
        text-transform: capitalize;
        border: none;
        padding: 0;
    }

    .status-history-item p {
        margin: 0;
        font-size: 0.9rem;
        color: #000000;
    }

    /* Responsive: stack the two columns on small screens */
    @media (max-width: 47rem) {
        .ticket-card {
            padding: 1.25rem;
            border-radius: 2.5rem;
        }
        .ticket-content {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        .ticket-status strong,
        .status {
            font-size: 1rem;
        }
    }
</style>


<!--________________________________
__________PAGE CONTENT_____________-->

<!-- Breadcrumb -->
<div class="page-navigation">
    <a href="dashboard.php">Dashboard/</a>
    <a href="my_faults.php">Fault Tickets/</a>
    <h3>Ticket Details</h3>
</div>

<!-- Main ticket card -->
<section class="ticket-card">
    <h1>Fault Ticket Details</h1>

    <!-- Status bar -->
    <div class="ticket-status">
        <strong>Ticket ID: <?php echo htmlspecialchars($ticket['TicketID']); ?></strong>

        <div class="status">
            <div class="status-dot <?php echo strtolower(str_replace(' ', '-', $ticket['Status'])); ?>"></div>
            <p class="status-text"><?php echo htmlspecialchars($ticket['Status']); ?></p>
        </div>
    </div>

    <!-- Content grid -->
    <div class="ticket-content">

        <!-- On the left: description and uploads -->
        <div class="ticket-description">
            <h2><?php echo htmlspecialchars($ticket['ServiceName']); ?></h2>

            <div class="line"></div>

            <p class="details-title">Details:</p>
            <div class="details-area">
                <?php echo nl2br(htmlspecialchars($ticket['Description'])); ?>
            </div>

            <div class="line"></div>

            <p class="uploads-title">Uploads:</p>
            <div class="uploads">
                <?php if ($photoResult->num_rows > 0) { ?>

                    <?php while ($photo = $photoResult->fetch_assoc()) { ?>
                    <a href="<?php echo htmlspecialchars($photo['FilePath']); ?>" target="_blank" class="upload-image">
                        <img src="<?php echo htmlspecialchars($photo['FilePath']); ?>"
                        alt="<?php echo htmlspecialchars($photo['FileName']); ?>">
                    </a>
                    <?php } ?>

                <?php } else { ?>

                    <p style="font-size: 0.75rem; color: #666666;">No images uploaded.</p>

                <?php } ?>
            </div>
        </div>

        <!-- Right: log date and log address -->
        <div class="ticket-meta">
            <div class="meta-item">
                <h2>Log Date</h2>
                <p><?php echo htmlspecialchars($ticket['DateSubmitted']); ?></p>
            </div>

            <div class="meta-item address">
                <h2>Log Address</h2>
                <p><?php echo htmlspecialchars($ticket['StreetAddress']); ?></p>
            </div>
        </div>

    </div>
</section>

<!-- Status history list -->
<section class="status-history">
    <h2>Status History</h2>

    <?php if ($historyResult->num_rows > 0) { ?>

        <?php while ($history = $historyResult->fetch_assoc()) { ?>
            <div class="status-history-item">
                <h3><?php echo htmlspecialchars($history['Status']); ?></h3>
                <?php if (!empty($history['Notes'])) { ?>
                    <p>Notes: <?php echo nl2br(htmlspecialchars($history['Notes'])); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

    <?php } else { ?>

        <p>No status history available for this ticket.</p>

    <?php } ?>
</section>

<?php
$stmt->close();
$historyStmt->close();
$photoStmt->close();
$conn->close();

require 'php/footer.php';
?>