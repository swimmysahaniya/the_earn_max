<?php
session_start();
include("includes/config.php");

// Fetch user mobile from session
$user_mobile = $_SESSION['user_mobile'] ?? '';
if (!$user_mobile) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $name = htmlspecialchars(strip_tags($_POST['name']));
    $address = htmlspecialchars(strip_tags($_POST['address']));
    $pan_number = strtoupper(htmlspecialchars(strip_tags($_POST['pan_number'])));
    $email_id = filter_var($_POST['email_id'], FILTER_VALIDATE_EMAIL);

    // Validate PAN number format (ABCDE1234F)
    if (!preg_match("/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/", $pan_number)) {
        echo "<script>alert('Invalid PAN Number format.');</script>";
        exit();
    }

    // File upload handling
    $upload_dir = "/Users/susheel/PycharmProjects/pythonProject1/myproject/public/static/pan_cards/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $pan_card_image = basename($_FILES["pan_card_image"]["name"]);
    $target_file = $upload_dir . $pan_card_image;
    $relative_path = "pan_cards/" . $pan_card_image;  // Store only relative path

    // Validate image type
    $allowed_types = ["jpg", "jpeg", "png"];
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, $allowed_types)) {
        echo "<script>alert('Only JPG, JPEG, and PNG files are allowed.');</script>";
        exit();
    }

    // Upload file if valid
    if (move_uploaded_file($_FILES["pan_card_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO myapp_kyc (user_mobile_id, name, address, pan_number, pan_card_image, email_id, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, '0', NOW())");
        $stmt->bind_param("ssssss", $user_mobile, $name, $address, $pan_number, $relative_path, $email_id);

        if ($stmt->execute()) {
            echo "<script>alert('KYC submitted successfully! Waiting for approval.');</script>";
        } else {
            echo "<script>alert('Error submitting KYC.');</script>";
        }
    } else {
        echo "<script>alert('File Upload Failed.');</script>";
    }
}
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<!-- Bootstrap KYC Form -->
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
        <h2 class="text-center mb-4">KYC Verification</h2>
        <form id="kyc-form" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" required></textarea>
            </div>

            <div class="mb-3">
                <label for="pan_number" class="form-label">PAN Number</label>
                <input type="text" class="form-control" id="pan_number" name="pan_number" required maxlength="10">
            </div>

            <div class="mb-3">
                <label for="email_id" class="form-label">Email ID</label>
                <input type="email" class="form-control" id="email_id" name="email_id" required>
            </div>

            <div class="mb-3">
                <label for="pan_card_image" class="form-label">Upload PAN Card Image</label>
                <input type="file" class="form-control" id="pan_card_image" name="pan_card_image" accept="image/*" required>
            </div>

            <input type="hidden" name="user_mobile" value="<?php echo htmlspecialchars($user_mobile); ?>">

            <button type="submit" class="btn btn-primary">Submit KYC</button>
        </form>
    </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
