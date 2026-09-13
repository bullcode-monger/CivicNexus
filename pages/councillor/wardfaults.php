<?php
session_start();
include "dbConnection.php";

// ensures the councillor is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// gets the logged-in councillor's ward
$councillorWard = $_SESSION['ward'];

// retrieves search text
$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// search value
$searchValue = "%" . $search . "%";

// retrieves tickets belonging to the councillor's ward
$sql = "SELECT
            f.TicketID,
            f.Title,
            f.StreetAddress,
            f.DateSubmitted,
            f.DateResolved,
            COALESCE(
                (
                    SELECT fs.Status
                    FROM faultticketstatus fs
                    WHERE fs.TicketID = f.TicketID
                    ORDER BY fs.StatusHistoryID DESC
                    LIMIT 1
                ),
                'Open'
            ) AS Status
        FROM faultticket f
        WHERE f.WardID = ?
        AND (
            CAST(f.TicketID AS CHAR) LIKE ?
            OR f.Title LIKE ?
            OR f.StreetAddress LIKE ?
            OR COALESCE(
                (
                    SELECT fs.Status
                    FROM faultticketstatus fs
                    WHERE fs.TicketID = f.TicketID
                    ORDER BY fs.StatusHistoryID DESC
                    LIMIT 1
                ),
                'Open'
            ) LIKE ?
        )
        ORDER BY f.DateSubmitted DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "issss",
    $councillorWard,
    $searchValue,
    $searchValue,
    $searchValue,
    $searchValue
);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="wardfaults.css">
    <title>MakhandaPulse - Ward Faults</title>
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

        <a href="wardfaults.php" class="menu-item active">
            <i class="fa-solid fa-list-check"></i>
            <span>Ward Faults</span>
        </a>

        <a href="generatesummary.php" class="menu-item">
            <i class="fa-solid fa-file-lines"></i>
            <span>Ward Summaries</span>
        </a>

        <a href="notifications.php" class="menu-item">
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

    <section class="ward-faults-section">

        <div class="page-header">

            <div>
                <h2>Ward Faults</h2>

                <p>
                    Faults reported in Ward
                    <?php echo htmlspecialchars($councillorWard); ?>
                </p>
            </div>

        </div>

        <form method="GET" action="wardfaults.php" class="search-container">

            <input
                type="text"
                name="search"
                placeholder="Search by ticket ID, issue, location or status..."
                class="search-bar"
                value="<?php echo htmlspecialchars($search); ?>">

            <button type="submit" class="search-btn">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>

        </form>

        <div class="table-container">

            <table>

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Date Reported</th>
                        <th>Manage</th>
                        <th></th>
                    </tr>

                </thead>

                <tbody>

                <?php
                if ($result->num_rows > 0) {

                    while ($row = $result->fetch_assoc()) {

                        // determines ticket status
                        $status = $row['Status'];

                        if ($status == "Open") {
                            $statusClass = "open";
                        } elseif ($status == "In Progress") {
                            $statusClass = "in-progress";
                        } elseif ($status == "Resolved") {
                            $statusClass = "resolved";
                        } else {
                            $statusClass = "open";
                        }
                ?>

                    <tr>

                        <td>
                            #<?php echo htmlspecialchars($row['TicketID']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['Title']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['StreetAddress']); ?>
                        </td>

                        <td>
                            <span class="status <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($status); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo date("d M Y", strtotime($row['DateSubmitted'])); ?>
                        </td>

                        <td>
                            <a
                                href="update.php?id=<?php echo $row['TicketID']; ?>"
                                class="update-btn">
                                Update
                            </a>
                        </td>

                        <td>
                            <a
                                href="view.php?id=<?php echo $row['TicketID']; ?>"
                                class="view-btn">
                                View
                            </a>
                        </td>

                    </tr>

                <?php
                    }

                } else {
                ?>

                    <tr>
                        <td colspan="7">
                            No faults found.
                        </td>
                    </tr>

                <?php
                }
                ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<footer>
    &copy; 2026 MakhandaPulse. All rights reserved.
</footer>

<?php
$stmt->close();
$conn->close();
?>

</body>
</html>

