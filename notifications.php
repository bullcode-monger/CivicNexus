<?php

session_start();
require 'php/dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("PPlease log in first.");
}

$userID = $_SESSION['userID'];

$sql  = "SELECT NotificationsID, Title, Message, DateSent
         FROM notifications
         WHERE UserID = ?
         ORDER BY DateSent DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

require 'php/header.php';
?>

<!--_______________________________________
________Notifications pages styles_________-->
<style>
    /* Header row with "Stay Informed" and the Mark All As Read button */
    .notifications-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 1.5625rem;
        padding-left: 0.625rem;
        padding-right: 0.625rem;
    }

    .notifications-header .stay-informed {
        font-size: 1rem;
        color: #333333;
        margin: 0 0 0.3125rem 0;
    }

    .notifications-header h1 {
        font-size: 2.5rem;
        font-weight: bold;
        color: #172A39;
        text-decoration: underline;
        margin: 0;
        border: none;
        padding: 0;
    }

    /* The Mark All As Read link, styled like a button */
    .mark-all-btn {
        background-color: #FFFFFF;
        color: #000000;
        border: 0.0625rem solid #172A39;
        border-radius: 0.5rem;
        padding: 0.625rem 1.25rem;
        font-size: 1rem;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .mark-all-btn:hover {
        background-color: #172A39;
        color: #FFFFFF;
        text-decoration: none;
    }

    /* The big beige card that holds all notification rows */
    .notifications-card {
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-radius: 2.5rem;
        padding: 1.875rem 2.5rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        margin-bottom: 2.5rem;
    }

    /* One row per notification */
    .notification-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 0;
        border-bottom: 0.0625rem solid #172A39;
    }

    /* Remove the divider from the very last row */
    .notification-row:last-child {
        border-bottom: none;
    }

    /* Left side of a row (title, message, date) */
    .notification-content {
        flex: 1;
        margin-right: 1.25rem;
    }

    .notification-content h3 {
        font-size: 1.1rem;
        color: #172A39;
        margin: 0 0 0.3125rem 0;
        border: none;
        padding: 0;
    }

    .notification-content p {
        margin: 0 0 0.3125rem 0;
        color: #000000;
        font-size: 0.95rem;
    }

    .notification-content .date {
        font-size: 0.8rem;
        color: #666666;
        font-style: italic;
    }

    /* Dismiss link, styled like a button */
    .dismiss-button {
        background-color: #172A39;
        color: #FFFFFF;
        border: none;
        border-radius: 0.375rem;
        padding: 0.5rem 1.5625rem;
        font-size: 1rem;
        font-weight: bold;
        text-decoration: none;
        flex-shrink: 0;
        display: inline-block;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .dismiss-button:hover {
        background-color: #9AC7BF;
        color: #172A39;
        text-decoration: none;
    }

    /* Message shown when the user has no notifications */
    .no-notifications {
        text-align: center;
        color: #172A39;
        font-weight: bold;
        padding: 1.875rem;
        font-size: 1rem;
    }

    /* Responsive */
    @media (max-width: 48rem) {
        .notifications-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.9375rem;
        }
        .notifications-header h1 {
            font-size: 2rem;
        }
        .notifications-card {
            padding: 1.25rem;
            border-radius: 1.5625rem;
        }
        .notification-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.9375rem;
        }
        .dismiss-button {
            width: 100%;
            text-align: center;
        }
    }
</style>


<!-- PAGE CONTENT-->

<!-- Breadcrumb -->
<div class="page-navigation">
    <a href="dashboard.php">Dashboard/</a>
    <h3>Notifications</h3>
</div>

<!-- Header with the Mark All As Read link -->
<section class="notifications-header">
    <div>
        <p class="stay-informed">Stay Informed</p>
        <h1>Notifications</h1>
    </div>
    <a href="php/mark_all_read.php" class="mark-all-btn">Mark All As Read</a>
</section>

<!-- Notification list -->
<section class="notifications-card">

    <?php if ($result->num_rows > 0) { ?>

        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="notification-row">
                <div class="notification-content">
                    <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                    <p><?php echo htmlspecialchars($row['Message']); ?></p>
                    <p class="date"><?php echo htmlspecialchars($row['DateSent']); ?></p>
                </div>
                <a href="php/dismiss_notification.php?dismiss=<?php echo $row['NotificationsID']; ?>"
                   class="dismiss-button">Dismiss</a>
            </div>
        <?php } ?>

    <?php } else { ?>

        <p class="no-notifications">No notifications available.</p>

    <?php } ?>

</section>

<?php

$stmt->close();
$conn->close();


require 'php/footer.php';
?>