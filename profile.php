<?php

session_start();
require 'php/dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("Please log in first.");
}

$userID = $_SESSION['UserID'];

// Get the user's basic account details
$userSql   = "SELECT FirstName, LastName, Email, DateRegistered
              FROM systemUsers
              WHERE UserID = ?";
$userStmt  = $conn->prepare($userSql);
$userStmt->bind_param("i", $userID);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    die("User account not found.");
}

// Query 2: get the community member's ward and address
$memberSql  = "SELECT cm.Address, w.WardName
               FROM communityMember cm
               JOIN ward w ON cm.WardID = w.WardID
               WHERE cm.UserID = ?";
$memberStmt = $conn->prepare($memberSql);
$memberStmt->bind_param("i", $userID);
$memberStmt->execute();
$member = $memberStmt->get_result()->fetch_assoc();
$memberStmt->close();

// Fall back to defaults if the user has no communityMember row yet
$address  = $member['Address']  ?? 'Not provided';
$wardName = $member['WardName'] ?? 'Ward 1';

// Pull in the shared header
require 'php/header.php';
?>

<!--____________________________________
________Profile page specific___________-->
<style>
    /* Page title block at the top */
    .profile-header {
        margin-bottom: 1.5625rem;
        padding-left: 0.625rem;
    }

    .profile-header h1 {
        font-size: 2rem;
        font-weight: bold;
        text-decoration: underline;
        margin-bottom: 0.3125rem;
        color: #172A39;
    }

    .profile-header p {
        font-size: 1rem;
        color: #333333;
    }

    /* Two-column grid that holds the two cards */
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.875rem;
        margin-bottom: 2.5rem;
    }

    /* Shared card style for both columns */
    .profile-card {
        background-color: #E9E4E0;
        border-radius: 1.875rem;
        border: 0.0625rem solid #CCCCCC;
        padding: 1.875rem;
        display: flex;
        flex-direction: column;
    }

    .profile-card h2 {
        font-size: 1.375rem;
        font-weight: bold;
        color: #172A39;
        margin-bottom: 1.5625rem;
        border-bottom: none;
        padding-bottom: 0;
    }

    /* Left column: the form */
    .profile-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        flex: 1;
    }

    .profile-form .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .profile-form .form-group label {
        font-size: 1rem;
        color: #000000;
        font-weight: normal;
    }

    .form-control {
        background-color: #FFFFFF;
        border: 0.0625rem solid #999999;
        border-radius: 0.5rem;
        padding: 0.75rem 0.9375rem;
        font-size: 1rem;
        font-family: inherit;
        outline: none;
    }

    .form-control:focus {
        border-color: #172A39;
        box-shadow: 0 0 0.3125rem rgba(23, 42, 57, 0.3);
    }

    /* Save Changes button */
    .profile-form .form-actions {
        margin-top: auto;
        padding-top: 1.25rem;
    }

    .profile-form .form-actions button {
        width: 100%;
        background-color: #172A39;
        color: #FFFFFF;
        border: none;
        border-radius: 0.625rem;
        padding: 0.9375rem;
        font-size: 1.125rem;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .profile-form .form-actions button:hover {
        background-color: #12203a;
    }

    /* Right column: Role Overview grid */
    .role-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.875rem;
    }

    .role-full {
        grid-column: span 2;
        margin-top: 1.25rem;
    }

    .role-label {
        font-size: 0.9375rem;
        font-weight: bold;
        color: #000000;
        margin-bottom: 0.3125rem;
    }

    .role-value {
        font-size: 1rem;
        color: #333333;
    }

    /* Responsive */
    @media (max-width: 48rem) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        .role-grid {
            grid-template-columns: 1fr;
        }
        .role-full {
            grid-column: span 1;
        }
    }
</style>


<!--_______________________
________Page Content_______-->

<!-- Page title -->
<div class="profile-header">
    <h1>Profile</h1>
    <p>Keep your details up to date</p>
</div>

<div class="profile-grid">

    <!-- Left column: Personal Details -->
    <section class="profile-card">
        <h2>Personal Details</h2>

        <form action="php/update_profile.php" method="post" class="profile-form">
            <div class="form-group">
                <label for="first-name">First Name</label>
                <input id="first-name" name="first-name" type="text" class="form-control"
                       value="<?php echo htmlspecialchars($user['FirstName']); ?>">
            </div>

            <div class="form-group">
                <label for="last-name">Last Name</label>
                <input id="last-name" name="last-name" type="text" class="form-control"
                       value="<?php echo htmlspecialchars($user['LastName']); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" class="form-control"
                       value="<?php echo htmlspecialchars($user['Email']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" name="update_profile">Save Changes</button>
            </div>
        </form>
    </section>

    <!-- Right column: Role Overview -->
    <section class="profile-card">
        <h2>Role Overview</h2>

        <div class="role-grid">
            <div>
                <div class="role-label">Role</div>
                <div class="role-value">Community Member</div>
            </div>

            <div>
                <div class="role-label">Member Since</div>
                <div class="role-value">
                    <?php echo date('d F Y', strtotime($user['DateRegistered'])); ?>
                </div>
            </div>

            <div class="role-full">
                <div class="role-label">Location</div>
                <div class="role-value">
                    <?php echo htmlspecialchars($address . ', ' . $wardName); ?>
                </div>
            </div>
        </div>
    </section>

</div>

<?php
$conn->close();

require 'php/footer.php';
?>