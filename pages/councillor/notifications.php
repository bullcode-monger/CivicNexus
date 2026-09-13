<?php
session_start();
include "dbConnection.php";

// ensures the councillor is logged in
if (isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// gets the logged-in councillor's ward
$ward = $_SESSION['ward'];

// retrieves notifications for the councillor's ward
$sql = "SELECT id, title, message, date_created
        FROM notifications
        WHERE ward = ?
        ORDER BY date_created DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ward);
$stmt->execute();

$result = $stmt->get_result();

// counts notifications for the councillor's ward
$countSql = "SELECT COUNT(*) AS notification_count
             FROM notifications
             WHERE ward = ?";

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $ward);
$countStmt->execute();

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$notificationCount = $countRow['notification_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="notifications.css">
    <title>MakhandaPulse - Notifications</title>
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
            <i class="fa-solid fa-gauge"></i>
            <span>Dashboard</span>
        </a>

        <a href="reportfault.php" class="menu-item">
            <i class="fa-solid fa-file-circle-plus"></i>
            <span>Report Faults</span>
        </a>

        <a href="mytickets.php" class="menu-item">
            <i class="fa-solid fa-ticket"></i>
            <span>My Tickets</span>
        </a>

        <a href="wardfaults.php" class="menu-item">
            <i class="fa-solid fa-list-check"></i>
            <span>Ward Faults</span>
        </a>

        <a href="generatesummary.php" class="menu-item">
            <i class="fa-solid fa-file-lines"></i>
            <span>Ward Summaries</span>
        </a>

        <a href="notifications.php" class="menu-item active">
            <i class="fa-solid fa-bell"></i>
            <span>Notifications</span>
        </a>

        <a href="logout.php" class="menu-item logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>
</aside>

<main class="main-content">

    <section class="notifications-section">

        <div class="page-header">
            <div>
                <h2>Notifications</h2>
                <p>
                    Notifications and announcements for Ward
                    <?php echo htmlspecialchars($ward); ?>
                </p>
            </div>

            <div class="notification-count">
                <span><?php echo $notificationCount; ?></span>
            </div>
        </div>

        <?php if ($result->num_rows > 0) { ?>

            <div class="notifications-list">

                <?php while ($row = $result->fetch_assoc()) { ?>

                    <div class="notification-card">

                        <div class="notification-card-header">
                            <h3>
                                <?php echo htmlspecialchars($row['title']); ?>
                            </h3>
                        </div>

                        <p class="notification-message">
                            <?php echo htmlspecialchars($row['message']); ?>
                        </p>

                        <small>
                            <?php echo date("d M Y", strtotime($row['date_created'])); ?>
                        </small>

                    </div>

                <?php } ?>

            </div>

        <?php } else { ?>

            <div class="no-notifications">
                <h3>No Notifications</h3>

                <p>
                    There are currently no notifications for Ward
                    <?php echo htmlspecialchars($ward); ?>.
                </p>
            </div>

        <?php } ?>

    </section>

</main>

<footer>
    <p>&copy; 2026 MakhandaPulse</p>
</footer>

<?php
$stmt->close();
$countStmt->close();
$conn->close();
?>

</body>
</html>

