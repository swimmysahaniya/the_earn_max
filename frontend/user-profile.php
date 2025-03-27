<?php
//ERROR_REPORTING(E_ALL);
include("includes/config.php"); // Ensure database connection
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

// Check if Profile data exists
$query = "SELECT name, address, email_id, profile_image FROM myapp_profile WHERE user_mobile_id = '$user_mobile'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if ($row) {
    // User has Profile data, fetch it
    $name = $row['name'];
    $address = $row['address'];
    $email_id = $row['email_id'];
    $profile_image = $row['profile_image'] ?? "images/user-image-1.jpg"; // Default image
} else {
    // No Profile record found, set empty values
    $name = "";
    $address = "";
    $email_id = "";
}

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST["fullName"]);
    $email_id = mysqli_real_escape_string($conn, $_POST["email"]);
    $address = mysqli_real_escape_string($conn, $_POST["address"]);
//print_r($_FILES);die;
    // File upload handling
    $target_dir = "images/uploads/profile/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $profile_image = basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $profile_image;
    $relative_path = $profile_image;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image type
    $allowed_types = ["jpg", "jpeg", "png"];
    if (!in_array($imageFileType, $allowed_types)) {
        echo "<p class='text-danger'>Only JPG, JPEG, and PNG files are allowed.</p>";
        $uploadOk = 0;
    }

    if ($uploadOk && move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Update existing Profile record
        $query = "UPDATE myapp_profile SET name = '$name', email_id = '$email_id', address = '$address', profile_image = '$relative_path' WHERE user_mobile_id = '$user_mobile'";
    } else {
        // Insert new Profile record
        $query = "INSERT INTO myapp_profile (user_mobile_id, name, email_id, address, profile_image, created_at) VALUES ('$user_mobile', '$name', '$email_id', '$address', '$relative_path', NOW())";
    }

    if (mysqli_query($conn, $query)) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: user-profile.php"); // Refresh page
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating profile!";
    }
}

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])) { ?>
                <div class="alert alert-success"><?= $_SESSION['success_message']; ?></div>
                <?php unset($_SESSION['success_message']); } ?>
            <?php if (isset($_SESSION['error_message'])) { ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message']; ?></div>
                <?php unset($_SESSION['error_message']); } ?>

            <!-- Card for Profile Update -->
            <div class="card shadow rounded-4">
                <div class="card-header btn-success text-white text-center">
                    <h4>Update Your Profile</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullName" id="fullName" placeholder="Enter your full name" value="<?= htmlspecialchars($name); ?>" required />
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" value="<?= htmlspecialchars($email_id); ?>" required />
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" value="<?= htmlspecialchars($_SESSION['user_mobile']); ?>" readonly />
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="3" placeholder="Enter your address"><?= htmlspecialchars($address); ?></textarea>
                        </div>

                        <!-- Profile Picture -->
                        <div class="mb-3">
                          <label for="profile_image" class="form-label">Profile Picture</label>
                          <input type="file" class="form-control" name="profile_image" id="profile_image" accept="image/*" />
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-success w-100">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile View Section -->
            <div class="card shadow rounded-4 mt-4">
                <div class="card-header btn-success text-white text-center">
                    <h4>Your Profile</h4>
                </div>
                <div class="card-body">
                  <div class="text-center mb-3">
                    <?php if (!empty($profile_image) && file_exists($profile_image)) { ?>
                        <img id="viewProfilePicture" class="profile-picture" src="<?= $profile_image; ?>" alt="<?= $name; ?>" />
                    <?php } else { ?>
                        <img id="viewProfilePicture" class="profile-picture" src="images/user-image-1.jpg" alt="<?= $name; ?>" />
                    <?php } ?>
                  </div>
                    <p><strong>Full Name:</strong> <?= $name; ?></p>
                    <p><strong>Email:</strong> <?= $email_id; ?></p>
                    <p><strong>Phone Number:</strong> <?= $_SESSION['user_mobile']; ?></p>
                    <p><strong>Address:</strong> <?= $address; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
