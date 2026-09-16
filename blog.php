<?php

session_start();
require 'php/dbconnection.php';

// Read the logged-in user's ID (0 if the user is not logged in (Guest), the blog is public)
$userID = $_SESSION['UserID'] ?? 0;

// Get the user's ward (may be missing if they haven't been assigned yet)
$userSql  = "SELECT w.WardID, w.WardName
             FROM communityMember cm
             JOIN ward w ON cm.WardID = w.WardID
             WHERE cm.UserID = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userID);
$userStmt->execute();
$userWard = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

// A statement stored to determine whether to run ward-specific queries
if ($userWard) {
    $wardID       = $userWard['WardID'];
    $wardName     = $userWard['WardName'];
    $hasWard      = true;
} else {
    $wardID       = 0;
    $wardName     = 'Ward not assigned';
    $hasWard      = false;
}

// Look or the ward's notices
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
    $noticesResult = false; // No ward, so no notices
}

// Get the two most recent notices for this ward
$noticeSql  = "SELECT NoticeID, Title, NoticeText, NoticeType, PublishDate
               FROM notices
               WHERE WardID = ?
               ORDER BY PublishDate DESC
               LIMIT 2";
$noticeStmt = $conn->prepare($noticeSql);
$noticeStmt->bind_param("i", $wardID);
$noticeStmt->execute();
$noticeResult = $noticeStmt->get_result();

// Get the three most recent faults across the whole community
$faultSql    = "SELECT t.TicketID, t.Title, t.Status, t.DateSubmitted, s.ServiceName
                FROM faultTicket t
                JOIN municipalService s ON t.ServiceID = s.ServiceID
                ORDER BY t.DateSubmitted DESC
                LIMIT 3";
$faultResult = $conn->query($faultSql);

require 'php/header.php';
?>

<!--_______________________________________
__________Blog specific styling___________-->
<style>
    /* The container for the Local Notices section */
    .local-notices {
        width: 100%;
        background-color: #E9E4E0;
        border: 1px solid #FFFFFF;
        border-radius: 30px;
        padding: 1.5rem 0.95rem 2rem 0.95rem;
        box-sizing: border-box;
        margin-bottom: 1.875rem;
    }

    /* Title row inside the section header */
    .section-header {
        height: 3.125rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.1875rem;
        box-sizing: border-box;
    }

    .section-header h1 {
        margin: 0;
        padding: 0;
        color: #000000;
        font-size: 1.5rem;
        font-weight: bold;
        border: none;
        text-decoration: none;
    }

    /* The "View All" link, styled like a button */
    .view-all-btn {
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        background-color: #FFFFFF;
        color: #000000;
        border: 0.0625rem solid #172A39;
        border-radius: 0.3125rem;
        font-size: 0.8rem;
        font-weight: bold;
        text-decoration: none;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .view-all-btn:hover {
        background-color: #172A39;
        color: #FFFFFF;
        text-decoration: none;
    }

    /* Grid that holds the notice cards */
    .notice-grid {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.1875rem;
        padding: 0 0.9375rem;
        box-sizing: border-box;
    }

    /* One notice card */
    .notice-card {
        position: relative;
        width: 100%;
        min-height: 10.75rem;
        background-color: #FFFFFF;
        border: 0.0625rem solid #172A39;
        border-radius: 1.4375rem;
        padding: 1.125rem;
        margin: 0;
        box-sizing: border-box;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .notice-card:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.3125rem 0.75rem rgba(23, 42, 57, 0.15);
    }

    .notice-card h2 {
        margin: 0 0 0.5rem 0;
        padding: 0;
        color: #000000;
        font-size: 1.2rem;
        font-weight: bold;
        border: none;
        line-height: 1.4;
    }

    .notice-content {
        width: 100%;
        margin: 0;
        padding: 0;
    }

    .notice-content p {
        margin: 0;
        font-size: 0.85rem;
        color: #333333;
        line-height: 1.4;
    }

    /* Thin line that appears under the notice text */
    .notice-content::after {
        content: "";
        display: block;
        width: 100%;
        height: 0.0625rem;
        background-color: #172A39;
        margin-top: 0.65rem;
    }

    /* "Read more..." link inside each notice card */
    .read-more {
        position: absolute;
        right: 1.125rem;
        bottom: 0.625rem;
        color: #000000;
        font-size: 0.8rem;
        font-weight: bold;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .read-more:hover {
        color: #172A39;
        text-decoration: underline;
    }

    /* Recent Faults section under the notices */
    .recent-faults {
        width: 100%;
    }

    .faults-header {
        height: 2.6875rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.25rem;
        box-sizing: border-box;
    }

    .faults-header h2 {
        margin: 0;
        padding: 0;
        color: #000000;
        font-weight: bold;
        border: none;
    }

    /* Round "+" button next to Recent Faults */
    .blog-add-fault-btn {
        width: 2.75rem;
        height: 2.75rem;
        background-color: #FFFFFF;
        border-radius: 50%;
        border: 0.125rem solid #172A39;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 0.575rem;
        text-decoration: none;
        box-shadow: 0 0.1875rem 0.5rem rgba(0, 0, 0, 0.25);
        transition: background-color 0.2s ease;
    }

    .blog-add-fault-btn img {
        width: 1.25rem;
        height: 1.25rem;
    }

    .blog-add-fault-btn:hover {
        background-color: #9AC7BF;
    }

    /* Column of fault cards */
    .fault-list {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.9375rem;
        padding: 0 1.25rem;
        box-sizing: border-box;
    }

    /* One fault card */
    .fault-card {
        position: relative;
        width: 100%;
        min-height: 7.625rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-sizing: border-box;
        background-color: #FFFFFF;
        border: 0.0938rem solid #172A39;
        border-radius: 0.9375rem;
        padding: 0.9375rem 1.375rem;
        margin: 0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .fault-card:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.3125rem 0.75rem rgba(23, 42, 57, 0.15);
    }

    .fault-info {
        width: auto;
        display: flex;
        flex-direction: column;
        gap: 0.3125rem;
    }

    .fault-info h3 {
        margin: 0;
        color: #000000;
        font-size: 1rem;
        font-weight: bold;
        line-height: 1.3;
    }

    .fault-info p {
        margin: 0;
        color: #555555;
        font-size: 0.85rem;
        font-weight: normal;
        line-height: 1.4;
    }

    /* "View" button on each fault card */
    .fault-view {
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-sizing: border-box;
        background-color: #172A39;
        color: #FFFFFF;
        border: 0.0625rem solid #172A39;
        border-radius: 0.375rem;
        font-size: 0.87rem;
        font-weight: bold;
        text-decoration: none;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .fault-view:hover {
        background-color: #9AC7BF;
        color: #172A39;
        text-decoration: none;
    }

    /* Message shown when there is nothing to list */
    .empty-state {
        text-align: center;
        color: #172A39;
        font-weight: bold;
        padding: 1.25rem;
        background-color: #E9E4E0;
        border-radius: 0.9375rem;
        border: 0.0625rem solid #172A39;
    }

    /* Responsive: stack the notice cards on small screens */
    @media (max-width: 43.75rem) {
        .notice-grid {
            grid-template-columns: 1fr;
        }
    }
</style>


<!--__________________________
_______PAGE CONTENT___________-->

<?php if ($noticesResult && $noticesResult->num_rows > 0) { ?>
    <?php while ($row = $noticesResult->fetch_assoc()) { ?>
        <article class="notice-box">
            <p><strong><?php echo htmlspecialchars($row['Title']); ?>:</strong>
               <?php echo htmlspecialchars($row['NoticeText']); ?></p>
        </article>
    <?php } ?>
<?php } else { ?>
    <article class="notice-box">
        <p>No notices available.</p>
    </article>
<?php } ?>

<!-- Local Notices section -->
<section class="local-notices">
    <div class="section-header">
        <h1>Local Notices &mdash; <?php echo htmlspecialchars($wardName); ?></h1>
        <a href="ward_notices.php" class="view-all-btn">View All</a>
    </div>

    <?php if ($noticeResult->num_rows > 0) { ?>

        <div class="notice-grid">
            <?php while ($notice = $noticeResult->fetch_assoc()) { ?>
                <article class="notice-card">
                    <div class="notice-content">
                        <h2><?php echo htmlspecialchars($notice['Title']); ?></h2>
                        <p><?php echo htmlspecialchars($notice['NoticeText']); ?></p>
                    </div>
                    <a href="ward_notices.php" class="read-more">Read more...</a>
                </article>
            <?php } ?>
        </div>

    <?php } else { ?>

        <div class="notice-grid">
            <article class="notice-card">
                <div class="notice-content">
                    <h2>No notices</h2>
                    <p>There are no local notices for your ward at the moment.</p>
                </div>
            </article>
        </div>

    <?php } ?>
</section>

<!-- Recent Faults section -->
<section class="recent-faults">
    <div class="faults-header">
        <h2>Recent Faults</h2>
        <a href="report_fault.php" class="blog-add-fault-btn">
            <img src="SVGs/add_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24 (1).svg" alt="Add Fault">
        </a>
    </div>

    <div class="fault-list">
        <?php if ($faultResult->num_rows > 0) { ?>

            <?php while ($fault = $faultResult->fetch_assoc()) { ?>
                <article class="fault-card">
                    <div class="fault-info">
                        <h3><?php echo htmlspecialchars($fault['ServiceName']); ?></h3>
                        <p><?php echo htmlspecialchars($fault['Title']); ?> &mdash; <?php echo htmlspecialchars($fault['Status']); ?></p>
                    </div>
                    <a href="track_fault.php?id=<?php echo $fault['TicketID']; ?>" class="fault-view">View</a>
                </article>
            <?php } ?>

        <?php } else { ?>

            <div class="empty-state">
                <p>No faults reported yet. Be the first to report one.</p>
            </div>

        <?php } ?>
    </div>
</section>

<?php
$noticeStmt->close();
$conn->close();

// Pull in the shared footer
require 'php/footer.php';
?>