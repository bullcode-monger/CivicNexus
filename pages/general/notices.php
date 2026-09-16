
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF - 8">
    <meta name="viewport" content="width=device-width, initail-scale=1.0">
<title>Notices | CivicNexus</title> 
    <link rel="stylesheet" href="styleslk.css">

</head>
<body>
    <header>
<div id="logo">
    <h1 id="Makhanda">Makhanda</h1>
     <h1 id="Pulse">Pulse</h1>
</div>

<nav id="nav-links">
    <a href="homegen.html">Home</a>
    <a href="aboutus.html">About Us</a>
    <a href="services.html">Services</a>
    <a href="notices.php">Ward Notices</a>
    <a href="contactus.html">Contact Us</a>
</nav>

<div id="profile-actions">
 <button type="button" onclick="window.location.href='signup.html'">Sign Up</button>
        <button type="button" onclick="window.location.href='Signin.html'">Sign In</button>
</div>
</header>
    <main>
    <div class="dashboard-grid-dashboard">
<section class="dashboard-card" style="width: 70%;">




<section class="dashboard-card">
    <div class="card-header">
     <h2>Notices</h2>   
    </div>
    
<?php
include 'lkdbconnect.php';

$sql = "SELECT * FROM notices ORDER BY PublishDate DESC";
$result = $conn->query($sql);
if($result -> num_rows > 0) {

//table headers
echo "<p><h2>All records found in the table </h2> </p>";
echo "<centre><table width = \"99%\" bgcolor = \"#e9e4e0\" border = \"1\"><tr bgcolor = \"#9ac7bf\">
<th>Ward</th>
<th>Title</th>
<th>Notice</th>
<th>Date</th>
</tr>"
;

while ( $row = $result->fetch_assoc()) {
echo "<tr><td>". $row["WardID"]. "</td><td>"
. $row["Title"]."</td><td>"
.$row["NoticeText"]."</td><td>"
.$row["PublishDate"]."</td>"

;
"</tr>";
}
}
else {
echo "<p>No Matching record found. Try using other search criteria </p>";
}
$conn->close();

echo "</table>";
?></a>

</section>
</main>
</div>
<footer>
<nav>
  <a href="">About Us</a>
  <a href="">Contact</a>
  <a href="">Help</a>
  <a href="">Terms and Conditions</a>  
</nav>
<p>&copy; 2026 MakhandaPulse. All Rights Reserved</p>
</footer>
</body>

</html>

