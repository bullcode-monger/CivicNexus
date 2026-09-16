<?php
// dashboard.php
session_start();
require 'php/dbconnection.php';

// 1. Check user is logged in
if (!isset($_SESSION['UserID'])) {
    die("Please run session_sim.php first to log in.");
}

$userID    = $_SESSION['UserID'];
$firstName = $_SESSION['firstName'] ?? 'User';

// 2. Get the user's ward (for the ward-identifier badge)
$userSql = "SELECT w.WardID, w.WardName
            FROM communityMember cm
            JOIN ward w ON cm.WardID = w.WardID
            WHERE cm.UserID = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userID);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userWard   = $userResult->fetch_assoc();
$userStmt->close();

// Get the user's account status
$statusSql = "SELECT AccountStatus FROM systemUsers WHERE UserID = ?";
$statusStmt = $conn->prepare($statusSql);
$statusStmt->bind_param("i", $userID);
$statusStmt->execute();
$accountStatus = $statusStmt->get_result()->fetch_assoc()['AccountStatus'] ?? 'pending';
$statusStmt->close();

// Check whether the user actually has a ward assigned
if ($userWard) {
    $wardID   = $userWard['WardID'];
    $wardName = $userWard['WardName'];
    $hasWard  = true;
} else {
    $wardID   = 0;
    $wardName = 'Ward not assigned';
    $hasWard  = false;
}

// 3. Count ACTIVE tickets (status = 'Open')
$activeSql = "SELECT COUNT(*) AS count
              FROM faultTicket
              WHERE UserID = ? AND Status = 'Open'";
$activeStmt = $conn->prepare($activeSql);
$activeStmt->bind_param("i", $userID);
$activeStmt->execute();
$activeCount = $activeStmt->get_result()->fetch_assoc()['count'];
$activeStmt->close();

// 4. Get the 2 most recent active tickets
$recentSql = "SELECT t.TicketID, t.DateSubmitted, s.ServiceName
              FROM faultTicket t
              JOIN municipalService s ON t.ServiceID = s.ServiceID
              WHERE t.UserID = ? AND t.Status = 'Open'
              ORDER BY t.DateSubmitted DESC
              LIMIT 2";
$recentStmt = $conn->prepare($recentSql);
$recentStmt->bind_param("i", $userID);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

// 5. Count resolved tickets (ticket history)
$historyCountSql = "SELECT COUNT(*) AS count
                    FROM faultTicket
                    WHERE UserID = ? AND Status = 'Resolved'";
$historyCountStmt = $conn->prepare($historyCountSql);
$historyCountStmt->bind_param("i", $userID);
$historyCountStmt->execute();
$historyCount = $historyCountStmt->get_result()->fetch_assoc()['count'];
$historyCountStmt->close();

// 6. Get the 2 most recent resolved tickets
$historySql = "SELECT t.TicketID, t.DateSubmitted, t.Status, s.ServiceName
               FROM faultTicket t
               JOIN municipalService s ON t.ServiceID = s.ServiceID
               WHERE t.UserID = ? AND t.Status = 'Resolved'
               ORDER BY t.DateSubmitted DESC
               LIMIT 2";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param("i", $userID);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

// Get the most recent 2 ward notices for the user's ward
// (only run this if the user actually has a ward)
if ($hasWard) {
    $noticesSql    = "SELECT Title, NoticeText
                      FROM notices
                      WHERE WardID = ?
                      ORDER BY PublishDate DESC
                      LIMIT 2";
    $noticesStmt   = $conn->prepare($noticesSql);
    $noticesStmt->bind_param("i", $wardID);
    $noticesStmt->execute();
    $noticesResult = $noticesStmt->get_result();
} else {
    $noticesStmt   = null;
    $noticesResult = false;
}
// 8. Count unread notifications
$notifCountSql = "SELECT COUNT(*) AS count
                  FROM notifications
                  WHERE UserID = ?";
$notifCountStmt = $conn->prepare($notifCountSql);
$notifCountStmt->bind_param("i", $userID);
$notifCountStmt->execute();
$notifCount = $notifCountStmt->get_result()->fetch_assoc()['count'];
$notifCountStmt->close();

// 9. Get the 2 most recent notifications
$notifSql = "SELECT NotificationsID, Title, Message, DateSent
             FROM notifications
             WHERE UserID = ?
             ORDER BY DateSent DESC
             LIMIT 2";
$notifStmt = $conn->prepare($notifSql);
$notifStmt->bind_param("i", $userID);
$notifStmt->execute();
$notifResult = $notifStmt->get_result();

// Include the shared header
require 'php/header.php';
?>

<!--_______________________________________________
___________Dashboard page specific styles__________-->
<style>
    /* Welcome banner */
    .welcome-banner-ward-identifier {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        margin-bottom: 20px;
    }

    .welcome-text h1 {
        font-size: 2rem;
        margin: 0;
        color: #000;
    }

    .ward-identifier {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ward-identifier p {
        font-size: 1.2rem;
        font-weight: bold;
        margin: 0;
        color: #000;
    }

    /* The coloured dot that reflects the user's account status */
    .status-dot {
        width: 1.25rem;              /* Dot width */
        height: 1.25rem;             /* Dot height */
        border-radius: 50%;          /* Turn the square into a circle */
        border: 0.0625rem  solid #172A39; /* Thin dark outline */
        display: inline-block;
    }

    /* Active account = green */
    .status-dot.active {
        background-color: #00b009;
    }

    /* Pending account = orange */
    .status-dot.pending {
        background-color: orangered;
    }

    /* Suspended account = red */
    .status-dot.suspended {
        background-color: red;
    }

    /* Deactivated account = grey */
    .status-dot.deactivated {
        background-color: grey;
    }

    /* Dashboard layout */
    .dashboard-layout {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .two-column-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .full-width {
        width: 100%;
    }

    /* Dashboard cards */
    .dashboard-card {
        background-color: #E9E4E0;
        border: 1px solid #FFFFFF;
        border-radius: 30px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .card-header h2,
    .card-header h3 {
        margin: 0;
        color: #172A39;
    }

    .card-footer-action {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }

    .dashboard-card article {
        background-color: #FFFFFF;
        border: 1px solid #172A39;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
    }

    .dashboard-card article p {
        margin-bottom: 5px;
    }

    /* Notice box style (used in Ward Notices) */
    .notice-box p {
        margin: 0;
        font-size: 0.9rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .welcome-banner-ward-identifier {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .two-column-row {
            grid-template-columns: 1fr;
        }
    }
</style>


<!--__________________________________
________________Page Content__________-->

<!-- Welcome banner -->
<section class="welcome-banner-ward-identifier">
    <div class="welcome-text">
        <h1>Welcome, <?php echo htmlspecialchars($firstName); ?></h1>
    </div>
    <div class="ward-identifier">
        <div class="status-dot <?php echo strtolower($accountStatus); ?>"></div>
        <p>Makhanda, <?php echo htmlspecialchars($wardName); ?></p>
    </div>
</section>

<div class="dashboard-layout">

    <!-- ACTIVE TICKETS -->
    <section class="dashboard-card full-width">
        <div class="card-header">
            <h2>Active Tickets [<?php echo $activeCount; ?>]</h2>
            <a href="my_faults.php"><button type="button">View All</button></a>
        </div>

        <?php if ($recentResult->num_rows > 0): ?>
            <?php while ($row = $recentResult->fetch_assoc()): ?>
                <article>
                    <p><?php echo htmlspecialchars($row['ServiceName']); ?></p>
                    <p style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($row['DateSubmitted']); ?></p>
                    <a href="track_fault.php?id=<?php echo $row['TicketID']; ?>">
                        <button type="button">View</button>
                    </a>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <article>
                <p>No active tickets. Report a fault to get started.</p>
            </article>
        <?php endif; ?>

        <div class="card-footer-action">
            <a href="report_fault.html"><button type="button">Add Fault</button></a>
        </div>
    </section>

    <!-- TWO COLUMN: HISTORY + WARD NOTICES -->
    <div class="two-column-row">

        <!-- TICKET HISTORY -->
        <section class="dashboard-card">
            <div class="card-header">
                <h3>Ticket History [<?php echo $historyCount; ?>]</h3>
                <a href="my_faults.php"><button type="button">View All</button></a>
            </div>

            <?php if ($historyResult->num_rows > 0): ?>
                <?php while ($row = $historyResult->fetch_assoc()): ?>
                    <article>
                        <p><?php echo htmlspecialchars($row['ServiceName']); ?></p>
                        <p style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($row['Status']); ?></p>
                        <a href="track_fault.php?id=<?php echo $row['TicketID']; ?>">
                            <button type="button">View</button>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <article><p>No ticket history available.</p></article>
            <?php endif; ?>
        </section>

        <!-- WARD NOTICES -->
        <section class="dashboard-card">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($wardName); ?> Notices</h3>
                <a href="ward_notices.php"><button type="button">View All</button></a>
            </div>

        <?php if ($noticesResult && $noticesResult->num_rows > 0) { ?>

            <?php while ($row = $noticesResult->fetch_assoc()) { ?>
                <article class="notice-box">
                    <p><strong><?php echo htmlspecialchars($row['Title']); ?>:</strong>
                    <?php echo htmlspecialchars($row['NoticeText']); ?></p>
                </article>
<?php } ?>

<?php } else { ?>

    <article class="notice-box">
        <p>No ward notices at the moment.</p>
    </article>

<?php } ?>
        </section>

    </div>

    <!-- Notification -->
    <section class="dashboard-card full-width">
        <div class="card-header">
            <h3>Notifications [<?php echo $notifCount; ?>]</h3>
            <a href="notifications.php"><button type="button">View All</button></a>
        </div>

        <?php if ($notifResult->num_rows > 0): ?>
            <?php while ($row = $notifResult->fetch_assoc()): ?>
                <article>
                    <p><strong><?php echo htmlspecialchars($row['Title']); ?>:</strong>
                       <?php echo htmlspecialchars($row['Message']); ?></p>
                    <a href="notifications.php"><button type="button">View</button></a>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <article><p>No notifications yet.</p></article>
        <?php endif; ?>
    </section>

</div>

<!--Floating add fault button-->
    <a href="report_fault.php" id="floating-add-btn">
    <img src="SVGs\add_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24 (1).svg" alt="Add Fault">
</a>

<?php
$recentStmt->close();
$historyStmt->close();
$noticesStmt->close();
$notifStmt->close();
$conn->close();

require 'php/footer.php';
?>