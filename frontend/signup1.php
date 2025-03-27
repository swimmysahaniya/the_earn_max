<?php
session_start();
include "includes/config.php"; // Ensure this file has correct database credentials

$errors = [];
$success_message = "";
$generated_referral_code = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["signup"])) {
    $mobile = trim($_POST["mobile"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $invitation_code = trim($_POST["invitation_code"]);

    // Validate input fields
    if (empty($mobile) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    // Check if mobile number is already registered
    $stmt = $conn->prepare("SELECT id FROM myapp_users WHERE mobile = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Mobile number already registered!";
    }
    $stmt->close();

    // Validate invitation code
    if (!empty($invitation_code)) {
        $stmt = $conn->prepare("SELECT referral_code FROM myapp_users WHERE referral_code = ? AND status = '1'");
        $stmt->bind_param("s", $invitation_code);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $errors[] = "Invalid invitation code!";
        }
        $stmt->close();
    } else {
        $invitation_code = NULL; // Allow null invited_by field
    }

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Generate unique referral code
        $generated_referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Insert user data
        $stmt = $conn->prepare("INSERT INTO myapp_users (mobile, password, referral_code, invited_by, created_at, status) VALUES (?, ?, ?, ?, NOW(), '0')");
        $stmt->bind_param("ssss", $mobile, $hashed_password, $generated_referral_code, $invitation_code);

        if ($stmt->execute()) {
            $_SESSION["user_mobile"] = $mobile;
            $_SESSION["referral_code"] = $generated_referral_code;

            // Display success message with new referral code
            $success_message = "Signup successful! <br>
            Your Referral Code: <b>$generated_referral_code</b> <br>
            Share this referral code to invite new users.";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!-- Display Referral Code After Registration -->
<?php if (!empty($success_message)) { ?>
    <div style="color: green; font-weight: bold; padding: 10px; border: 1px solid green;">
        <?php echo $success_message; ?>
    </div>
<?php } ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/logo.png">
    <title>The Earn Max - Signup</title>
    <link href="login.css" rel="stylesheet">
</head>

<body>
    <br>
    <header>
        <h1 class="heading">The Earn Max</h1>
    </header>

    <div class="container">
         <!-- <div class="slider1"></div> -->
        <div class="btn">
            <a href="login">
    <button class="login <?php echo ($current_page == 'login') ? 'active' : ''; ?>">Login</button>
</a>
<a href="signup">
    <button class="signup <?php echo ($current_page == 'signup') ? 'active' : ''; ?>">Signup</button>
</a>

        </div>
        <!-- Error Messages -->
        <?php if (!empty($errors)) { ?>
            <div class="error-messages">
                <?php foreach ($errors as $error) {
                    echo "<p style='color:red;'>$error</p>";
                } ?>
            </div>
        <?php } ?>

        <!-- Signup Form -->
        <div class="signup-box">

            <form method="POST">
                <input type="text" name="mobile" placeholder="Enter your mobile number" class="name ele" required>
                <input type="password" name="password" placeholder="Password" class="name ele" required>
                <input type="password" name="confirm_password" placeholder="Confirm password" class="name ele" required>
                <input type="text" name="invitation_code" placeholder="Please enter the invitation code" class="name ele" required>
                <button type="submit" name="signup" class="clkbtn">Signup</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 The Earn Max. All rights reserved.</p>
        <div class="social-links">
            <a href="https://www.facebook.com" target="_blank">Facebook</a>
            <a href="https://www.twitter.com" target="_blank">Twitter</a>
            <a href="https://www.instagram.com" target="_blank">Instagram</a>
        </div>
        <p>
            <a href="privacy-policy.html">Privacy Policy</a> |
            <a href="terms-and-conditions.html">Terms & Conditions</a>
        </p>
    </footer>
</body>

</html>
