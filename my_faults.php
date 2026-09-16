<?php

session_start();
require 'php/dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("Please log in first.");
}

$userID = $_SESSION['UserID'];

// Read the filter values from the URL (empty by default)
$search   = trim($_GET['search']   ?? '');
$status   = trim($_GET['status']   ?? '');
$category = trim($_GET['category'] ?? '');

// Base query: fetch this user's tickets joined with their service name
$sql = "SELECT t.TicketID, t.Title, t.DateSubmitted, t.Status, s.ServiceName
        FROM faultTicket t
        JOIN municipalService s ON t.ServiceID = s.ServiceID
        WHERE t.UserID = ?";

// Building the list of parameters for the prepared statement
$types  = "i";
$params = [$userID];

// If the user searched, add a LIKE clause on TicketID, ServiceName, and Title
if ($search !== '') {
    $sql .= " AND (CAST(t.TicketID AS CHAR) LIKE ? OR s.ServiceName LIKE ? OR t.Title LIKE ?)";
    $searchValue = "%" . $search . "%";
    $types .= "sss";
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

// If the user picked a status, add an equality filter
if ($status !== '') {
    $statusMap = [
        'open'        => 'Open',
        'in_progress' => 'In Progress',
        'resolved'    => 'Resolved'
    ];
    if (isset($statusMap[$status])) {
        $sql .= " AND t.Status = ?";
        $types .= "s";
        $params[] = $statusMap[$status];
    }
}

// If the user picked a category, map it to its ServiceID and filter
$categoryMap = [
    'water'       => 1,
    'electricity' => 2,
    'roads'       => 3,
    'sanitation'  => 4,
    'fire'        => 5,
    'animal'      => 6
];
if ($category !== '' && isset($categoryMap[$category])) {
    $sql .= " AND t.ServiceID = ?";
    $types .= "i";
    $params[] = $categoryMap[$category];
}

$sql .= " ORDER BY t.DateSubmitted DESC"; // Order the results newest first

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

require 'php/header.php';
?>

<!--___________________________________________
_________My Faults page specific styling________-->
<style>
    /* Filter card that holds the search form */
    .search-filter {
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1.875rem;
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }

    /* Layout of the form inside the filter card */
    .search-filter form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 0.9375rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: bold;
        color: #172A39;
        margin-bottom: 0.3125rem;
        font-size: 0.875rem;
    }

    .filter-group input[type="text"],
    .filter-group select {
        width: 100%;
        background-color: #FFFFFF;
        border: 0.0625rem solid #172A39;
        padding: 0.625rem;
        border-radius: 0.3125rem;
        color: #000000;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 1rem;
        box-sizing: border-box;
    }

    .filter-actions {
        display: flex;
        gap: 0.625rem;
    }

    .filter-actions button {
        background-color: #172A39;
        color: #FFFFFF;
        border: none;
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        border-radius: 0.3125rem;
        cursor: pointer;
        font-weight: bold;
    }

    .filter-actions button:hover {
        opacity: 0.85;
    }

    /* Reset is a link that reloads the page with no filters */
    .reset-button {
        background-color: #E9E4E0;
        color: #172A39;
        border: 0.0625rem solid #172A39;
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
        font-weight: bold;
        text-decoration: none;
        border-radius: 0.3125rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .reset-button:hover {
        opacity: 0.85;
        color: #172A39;
        text-decoration: none;
    }

    /* Table that lists the tickets */
    .faults-table-container {
        width: 100%;
        overflow-x: auto;
    }

    .faults-table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .faults-table-container thead {
        background-color: #172A39;
        color: #FFFFFF;
    }

    .faults-table-container thead th {
        text-align: left;
        padding: 0.75rem;
        font-weight: bold;
    }

    .faults-table-container tbody tr {
        background-color: #E9E4E0;
        border-bottom: 0.0625rem solid #172A39;
    }

    .faults-table-container tbody tr:hover {
        background-color: #FFFFFF;
    }

    .faults-table-container tbody td {
        padding: 0.75rem;
        color: #000000;
    }

    .faults-table-container tbody td a {
        color: #172A39;
        font-weight: bold;
        text-decoration: underline;
    }

    .faults-table-container tbody td a:hover {
        color: #000000;
    }

    /* Message shown if no tickets match the filters */
    .no-records {
        text-align: center;
        color: #172A39;
        padding: 1.25rem;
        font-weight: bold;
    }

    /* Responsive: stack the filter form on small screens */
    @media (max-width: 48rem) {
        .search-filter form {
            grid-template-columns: 1fr;
        }
    }
</style>


<!-- ____________________________
__________PAGE CONTENT___________-->

<!-- Breadcrumb -->
<div class="page-navigation">
    <a href="dashboard.php">Dashboard/</a>
    <h3>Fault Tickets</h3>
</div>

<!-- Search and filter card -->
<section class="search-filter">
    <form action="my_faults.php" method="GET">
        <div class="filter-group">
            <label for="search">Search</label>
            <input type="text" id="search" name="search"
                   placeholder="Search by Ticket ID or Category"
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">-- All Statuses --</option>
                <option value="open"        <?php if ($status === 'open')        { echo 'selected'; } ?>>Open</option>
                <option value="in_progress" <?php if ($status === 'in_progress') { echo 'selected'; } ?>>In Progress</option>
                <option value="resolved"    <?php if ($status === 'resolved')    { echo 'selected'; } ?>>Resolved</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">-- All Categories --</option>
                <option value="water"       <?php if ($category === 'water')       { echo 'selected'; } ?>>Water</option>
                <option value="electricity" <?php if ($category === 'electricity') { echo 'selected'; } ?>>Electricity</option>
                <option value="roads"       <?php if ($category === 'roads')       { echo 'selected'; } ?>>Roads</option>
                <option value="sanitation"  <?php if ($category === 'sanitation')  { echo 'selected'; } ?>>Sanitation</option>
                <option value="fire"        <?php if ($category === 'fire')        { echo 'selected'; } ?>>Fire</option>
                <option value="animal"      <?php if ($category === 'animal')      { echo 'selected'; } ?>>Animal Control</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit">Apply Filters</button>
            <a href="my_faults.php" class="reset-button">Reset</a>
        </div>
    </form>
</section>

<!-- Table of tickets -->
<section class="faults-table-container">
    <table>
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Category</th>
                <th>Date Submitted</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0) { ?>

                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($row['TicketID']); ?></td>
                        <td><?php echo htmlspecialchars($row['ServiceName']); ?></td>
                        <td><?php echo htmlspecialchars($row['DateSubmitted']); ?></td>
                        <td><?php echo htmlspecialchars($row['Status']); ?></td>
                        <td>
                            <a href="track_fault.php?id=<?php echo $row['TicketID']; ?>">View Details</a>
                        </td>
                    </tr>
                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="5" class="no-records">No faults found. You haven't reported any issues yet.</td>
                </tr>

            <?php } ?>
        </tbody>
    </table>
</section>

<!-- Floating add fault button -->
<a href="report_fault.php" id="floating-add-btn">
    <img src="SVGs/add_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24 (1).svg" alt="Add Fault">
</a>

<?php
// Close the database connection
$stmt->close();
$conn->close();

// Pull in the shared footer
require 'php/footer.php';
?>