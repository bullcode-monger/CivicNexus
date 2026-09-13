<?php
session_start();

include "dbConnection.php";

// retrieve the logged-in councillor's information
$username = $_SESSION['username'];
$ward = $_SESSION['ward'];


// retrieve the 20 most recent tickets that belong to the councillor's ward
$sql = "SELECT TicketID, Title, StreetAddress, Status, DateReported
        FROM faultticket
        WHERE WardID = ?
        ORDER BY DateReported DESC
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ward);
$stmt->execute();
$result = $stmt->get_result();

// retrieve the total number of tickets in the councillor's ward
$sql_total = "SELECT COUNT(*) AS total
              FROM faultticket
              WHERE WardID = ?";

$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $ward);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$totalTickets = $result_total->fetch_assoc()['total'];
$stmt_total->close();

// retrieve the number of open tickets in the councillor's ward
$sql_open = "SELECT COUNT(*) AS open
             FROM faultticket
             WHERE WardID = ?
             AND Status = 'Open'";

$stmt_open = $conn->prepare($sql_open);
$stmt_open->bind_param("i", $ward);
$stmt_open->execute();
$result_open = $stmt_open->get_result();
$openTickets = $result_open->fetch_assoc()['open'];
$stmt_open->close();

// retrieve the number of in progress tickets in the councillor's ward
$sql_progress = "SELECT COUNT(*) AS progress
                 FROM faultticket
                 WHERE WardID = ?
                 AND Status = 'In Progress'";

$stmt_progress = $conn->prepare($sql_progress);
$stmt_progress->bind_param("i", $ward);
$stmt_progress->execute();
$result_progress = $stmt_progress->get_result();
$progressTickets = $result_progress->fetch_assoc()['progress'];
$stmt_progress->close();

// retrieve the number of resolved tickets in the councillor's ward
$sql_resolved = "SELECT COUNT(*) AS resolved
                 FROM faultticket
                 WHERE WardID = ?
                 AND Status = 'Resolved'";

$stmt_resolved = $conn->prepare($sql_resolved);
$stmt_resolved->bind_param("i", $ward);
$stmt_resolved->execute();
$result_resolved = $stmt_resolved->get_result();
$resolvedTickets = $result_resolved->fetch_assoc()['resolved'];
$stmt_resolved->close();

// retrieve the number of tickets in each service category
$sql_category = "SELECT ServiceID, COUNT(*) AS total
                 FROM faultticket
                 WHERE WardID = ?
                 GROUP BY ServiceID";

$stmt_category = $conn->prepare($sql_category);
$stmt_category->bind_param("i", $ward);
$stmt_category->execute();
$result_category = $stmt_category->get_result();

// create arrays for the category chart
$categoryLabels = [];
$categoryData = [];

while ($category = $result_category->fetch_assoc()) {
    $categoryLabels[] = "Service " . $category['ServiceID'];
    $categoryData[] = $category['total'];
}

$stmt_category->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Councillor Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <a href="dashboard.php" class="menu-item active">
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


<main class="dashboard-content">

    //welcome section
    <div class="welcome">
        <h3>
            Welcome <?php echo $username; ?>
        </h3>

        <small>
            Ward Councillor - Ward <?php echo $ward; ?>
        </small>
    </div>

    <br>

    //ticket summary
    <section class="ticket-summary">

        //sum of tickest submitted
        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>

            <div>
                <span>Total Tickets</span>
                <strong>
                    <?php echo $totalTickets; ?>
                </strong>
            </div>

        </div>

        //sum of tickest currently open
        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>

            <div>
                <span>Open Tickets</span>
                <strong>
                    <?php echo $openTickets; ?>
                </strong>
            </div>

        </div>

        //sum of tickest still in progess
        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-spinner"></i>
            </div>

            <div>
                <span>In Progress</span>
                <strong>
                    <?php echo $progressTickets; ?>
                </strong>
            </div>

        </div>

        //sum of tickets currently resolved
        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div>
                <span>Resolved Tickets</span>
                <strong>
                    <?php echo $resolvedTickets; ?>
                </strong>
            </div>

        </div>

    </section>

    <br>

    //dashboard visualisations
    <section class="visualisation-cards">

        //tickets by category pie chart
        <div class="visualisation-card">

            <h2>Tickets by Category</h2>
            <p class="card-description">
                Tickets reported in Ward <?php echo $ward; ?>, grouped by service category.
            </p>

            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>

        </div>


        //tickets by status bar chart
        <div class="visualisation-card">

            <h2>Tickets by Status</h2>
            <p class="card-description">
                Current status of tickets reported in Ward <?php echo $ward; ?>.
            </p>

            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>

        </div>

    </section>

    <br>

    //recently opened tickets
    <section class="recent-tickets">

        <div class="tickets-header">
            <h2>
                Recent Tickets - Ward
                <?php echo $ward; ?>
            </h2>
            <a href="generatesummary.php">
                <button class="summary-btn">
                    Generate Summary
                </button>
            </a>
        </div>

        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Issue</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Date Reported</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                <?php
                
                if ($result->num_rows > 0) {  // check if tickets were found                    
                    while ($ticket = $result->fetch_assoc()) {  // display each ticket                        
                        $status = strtolower( // convert status to lowercase for css
                            str_replace(
                                " ",
                                "-",
                                $ticket['Status']
                            )
                        );
                ?>


                    <tr>
                        <td>
                            <?php echo $ticket['TicketID']; ?>
                        </td>

                        <td>
                            <?php echo $ticket['Title']; ?>
                        </td>

                        <td>
                            <?php echo $ticket['StreetAddress']; ?>
                        </td>

                        <td>
                            <span class="status <?php echo $status; ?>">
                                <?php echo $ticket['Status']; ?>
                            </span>
                        </td>

                        <td>
                            <?php echo date(
                                "d M Y",
                                strtotime(
                                    $ticket['DateReported']
                                )
                            );
                            ?>
                        </td>

                        <td>
                            <a href="view.php?id=<?php echo $ticket['TicketID']; ?>">
                                <button class="view-btn">
                                    View
                                </button>
                            </a>
                        </td>

                    </tr>

                <?php

                    }

                } else { ?>


                    //no tickets found
                    <tr>
                        <td colspan="6">
                            No tickets have been reported in Ward
                            <?php
                            echo $ward;
                            ?>.
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


<script>

    // send the category data from PHP to JavaScript

    const categoryLabels = <?php echo json_encode($categoryLabels); ?>;
    const categoryData = <?php echo json_encode($categoryData); ?>;


    // send the status data from PHP to JavaScript

    const statusData = {
        open: <?php echo $openTickets; ?>,
        progress: <?php echo $progressTickets; ?>,
        resolved: <?php echo $resolvedTickets; ?>
    };

</script>

<script src="dashboard.js"></script>

</body>

</html>

<?php

// close the statement and database connection
$stmt->close();
$conn->close();

?>

