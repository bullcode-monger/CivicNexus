<?php

session_start();
require 'php/dbconnection.php';

if (!isset($_SESSION['UserID'])) {
    die("Please run session_sim.php first to log in.");
}

// Get the list of wards so the dropdown always matches the database
$wardSql    = "SELECT WardID, WardName FROM ward ORDER BY WardID ASC";
$wardResult = $conn->query($wardSql);

require 'php/header.php';
?>

<!-- Report fault specific styling-->
<style>
    /* Title above the form */
    .log-issue-banner h1 {
        text-align: center;
        color: #000000;
        margin-bottom: 0.625rem;
    }

    /* Narrow container that holds the form */
    .report-form-container {
        max-width: 40.625rem;
        margin: 0 auto;
        padding-bottom: 3.125rem;
    }

    /* Each numbered step in the form */
    .form-group {
        text-align: left;
        margin-bottom: 1.5625rem;
        font-size: 1.25rem;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        color: #000000;
        margin-bottom: 0.5rem;
    }

    #description {
        padding-top: 1.25rem;
    }

    /* Shared styling for select, textarea and text input */
    .form-group select,
    .form-group textarea,
    .form-group input[type="text"] {
        width: 100%;
        padding: 0.75rem;
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-radius: 0.3125rem;
        color: #000000;
        font-family: Helvetica, Arial, sans-serif;
        box-sizing: border-box;
        font-size: 1rem;
    }

    .form-group textarea {
        resize: vertical;
    }

    /* Location section heading */
    .insert-location label {
        font-weight: bold;
        font-size: 1.25rem;
        color: #000000;
        margin-bottom: 0.5rem;
        display: block;
    }

    /* The address input under the location heading */
    .insert-location input[type="text"] {
        width: 100%;
        padding: 1.25rem 0.75rem; 
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-radius: 0.3125rem;
        color: #000000;
        font-family: Helvetica, Arial, sans-serif;
        box-sizing: border-box;
        font-size: 1rem;
    }

    /* The clickable box that opens the file picker */
    .custom-upload-box {
        position: relative;
        width: 100%;
        height: 12.5rem;
        background-color: #E9E4E0;
        border: 0.0625rem solid #172A39;
        border-radius: 0.3125rem;
        cursor: pointer;
        display: block;
        margin-top: 0.3125rem;
        overflow: hidden;
    }

    /* Default upload icon shown before a file is chosen */
    #upload-icon {
        position: absolute;
        height: 12.5rem;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    #upload-icon:hover{
        opacity: 0.5;
    }

    /* Preview of the chosen image */
    #upload-preview {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 90%;
        max-height: 90%;
        border-radius: 0.5rem;
        object-fit: contain;
    }

        #upload-preview:hover {
            opacity: 0.9;
    }

    /* Row of buttons at the bottom of the form */
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 3.125rem;
        gap: 1.875rem;
    }

    .form-actions button {
        width: 11.25rem;
        font-weight: bold;
        border-radius: 0.3125rem;
        cursor: pointer;
        border: none;
        padding: 0.9375rem;
    }

    /* Clear Form button */
    .form-actions button[type="reset"] {
        background-color: #E9E4E0;
        color: #172A39;
        border: 0.0625rem solid #172A39;
    }

    /* Submit button */
    .form-actions button[type="submit"] {
        background-color: #172A39;
        color: #FFFFFF;
    }

    .form-actions button:hover {
        opacity: 0.85;
    }
</style>


<!--______________________________
_____________PAGE CONTENT_________-->

<!-- Breadcrumb -->
<div class="page-navigation">
    <a href="dashboard.php">Dashboard/</a>
    <h3>Fault Ticket</h3>
</div>

<section>
    <div class="log-issue-banner">
        <h1>Log an Issue</h1>
    </div>
</section>

<!-- The report form -->
<section class="report-form-container">
    <form action="php/submit_ticket.php" method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label for="category">1. Select a Category</label>
            <select id="category" name="category" required>
                <option value="">-- Select a Category --</option>
                <option value="water">Water Supply</option>
                <option value="electricity">Electrical Faults</option>
                <option value="roads">Road Damage</option>
                <option value="sanitation">Sanitation</option>
                <option value="fire">Fire</option>
                <option value="animal">Animal Control</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description">2. Briefly describe issue</label>
            <textarea id="description" name="description" rows="4" maxlength="300"
                      placeholder="Describe issue" required></textarea>
        </div>

        <div class="form-group">
            <label for="ward">3. Select Ward</label>
            <select id="ward" name="ward" required>
                <option value="">-- Select a Ward --</option>
                <?php while ($ward = $wardResult->fetch_assoc()) { ?>
                    <option value="<?php echo $ward['WardID']; ?>">
                        <?php echo htmlspecialchars($ward['WardName']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="insert-location">
            <label for="address">4. Insert location</label>
            <input type="text" id="address" name="address" placeholder="Enter street address" required>
        </div>

        <div class="form-group" style="margin-top: 1.5625rem;">
            <label>5. Upload image</label>

            <input type="file" id="image" name="image" accept=".jpg, .jpeg, .png" style="display: none;">

            <label for="image" class="custom-upload-box">
                <img id="upload-icon" src="SVGs/file-upload-svgrepo-com.svg" alt="Upload">
                <img id="upload-preview" src="" alt="Preview" style="display: none;">
            </label>
        </div>

        <div class="form-actions">
            <button type="reset">Clear Form</button>
            <button type="submit">Submit</button>
        </div>

    </form>
</section>

<?php
$conn->close();

require 'php/footer.php';
?>

<script>
    document.getElementById('image').addEventListener('change', function (event) {
        const file    = event.target.files[0];
        const preview = document.getElementById('upload-preview');
        const icon    = document.getElementById('upload-icon');

        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            icon.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });
</script>

</body>
</html>