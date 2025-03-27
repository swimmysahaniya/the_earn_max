<?php
session_start();
include("includes/config.php");

if (isset($_SESSION["user_mobile"])) {
    $user_mobile = $_SESSION["user_mobile"];
    $logout_time = date("Y-m-d H:i:s");

    // Update logout time and calculate session duration
    $query = "
        UPDATE myapp_useractivity
        SET logout_time = '$logout_time',
            session_duration = TIMEDIFF('$logout_time', login_time)
        WHERE user_mobile_id = '$user_mobile'
        AND logout_time IS NULL
        ORDER BY login_time DESC
        LIMIT 1";

    mysqli_query($conn, $query);
}

// Destroy session and redirect to login
session_destroy();
setcookie("user_mobile", "", time() - 3600, "/");
header("Location: login.php");
exit();
?>