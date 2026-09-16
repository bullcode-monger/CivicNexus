<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Work out which page the user is currently on, e.g. "dashboard.php"
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MakhandaPulse</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <div id="logo">
        <h1 id="Makhanda">Makhanda</h1>
        <h1 id="Pulse">Pulse</h1>
    </div>

    <nav id="nav-links">
        <a href="blog.php"      class="<?php if ($currentPage === 'blog.php')      { echo 'active'; } ?>">Blog</a>
        <a href="dashboard.php" class="<?php if ($currentPage === 'dashboard.php') { echo 'active'; } ?>">Dashboard</a>
        <a href="ward_notices.php" class="<?php if ($currentPage === 'ward_notices.php') { echo 'active'; } ?>">Ward Notices</a>
    </nav>

    <div id="header-actions">
        <a href="notifications.php" id="notification-bell" class="notif-btn">
            <img src="SVGs/notifications_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" alt="Notifications">
        </a>
        <a href="profile.php" id="profile-btn">Profile</a>
    </div>
</header>
<main>