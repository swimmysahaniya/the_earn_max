<?php
session_start();
include "includes/config.php";
$current_page = basename($_SERVER['PHP_SELF'], ".php");

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    // Login Form Handling
    $mobile = trim($_POST["mobile"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT password FROM users WHERE mobile = ?");
    $stmt->bind_param("s", $mobile);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_mobile"] = $mobile;
            header("Location: ./"); // Redirect to dashboard
            exit();
        } else {
            $errors[] = "Invalid mobile numnber or password!";
        }
    } else {
        $errors[] = "User not found!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="images/logo.png">
    <title>The Earn Max</title>
    <link href="login.css" rel="stylesheet">
</head>
<body>
    <br>
    <header>
        <h1 class="heading">The Earn Max</h1>
    </header>

    <div class="container">
          <!-- <div class="slider"></div> -->
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
                    echo "<center><p style='color:red;'>$error</p></center>";
                } ?>
            </div>
        <?php } ?>
<br/>
        <!-- Login Form -->
        <div class="login-box">
            <form method="POST">
                <input type="text" name="mobile" placeholder="Please enter your mobile number" class="name ele" required>
                <input type="password" name="password" placeholder="Please enter your login password" class="name ele" required>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        &nbsp;Remember username & password
                    </label>
                </div>
                <button type="submit" name="login" class="clkbtn">Login</button>
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