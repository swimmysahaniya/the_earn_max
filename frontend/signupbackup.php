<?php
session_start();
include "includes/config.php";
$current_page = basename($_SERVER['PHP_SELF'], ".php");
$errors = [];
$success_message = "";
$generated_referral_code = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["signup"])) {
    // Signup Form Handling
    $mobile = trim($_POST["mobile"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $invitation_code = trim($_POST["invitation_code"]);

    // Validate passwords
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    // Check if the invitation code exists
    $stmt = $conn->prepare("SELECT referral_code FROM users WHERE referral_code = ?");
    $stmt->bind_param("s", $invitation_code);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        $errors[] = "Invalid invitation code!";
    }
    $stmt->close();

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Generate unique referral code for the new user
        $generated_referral_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Insert user data
        $stmt = $conn->prepare("INSERT INTO users (mobile, password, referral_code, invited_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $mobile, $hashed_password, $generated_referral_code, $invitation_code);

        if ($stmt->execute()) {
            $_SESSION["user_mobile"] = $mobile;
            $success_message = "Signup successful! <br> Your Invitation Code: <b>$invitation_code</b> <br> Your Referral Code: <b>$generated_referral_code</b>";
        } else {
            $errors[] = "Mobile number already registered!";
        }

        $stmt->close();
    }
}
?>


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
