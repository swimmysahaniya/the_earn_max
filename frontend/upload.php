<?php
session_start();
include("includes/config.php"); // Ensure database connection

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["payment_screenshot"])) {
    $user_mobile = $_SESSION["user_mobile"];
    $amount = $_POST["amount"];
    $transaction_code = $_POST["transaction_code"];

    // Define Upload Directory
    $target_dir = "membership/";
    
    // Ensure the directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Generate Unique File Name
    $file_name = time() . "_" . basename($_FILES["payment_screenshot"]["name"]);
    $target_file = $target_dir . $file_name;

    // Check if the file is actually uploaded
    if ($_FILES["payment_screenshot"]["error"] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES["payment_screenshot"]["tmp_name"], $target_file)) {
            // Check if the user already has a payment record
            $check_stmt = $conn->prepare("SELECT id FROM myapp_payment WHERE user_mobile_id = ?");
            $check_stmt->bind_param("s", $user_mobile);
            $check_stmt->execute();
            $check_stmt->store_result();
            $num_rows = $check_stmt->num_rows;
            $check_stmt->close();

            if ($num_rows > 0) {
                // User has a payment record, update it
                $stmt = $conn->prepare("UPDATE myapp_payment SET amount = ?, screenshot = ?, transaction_code = ?, status = '0', created_at = NOW() WHERE user_mobile_id = ?");
                $stmt->bind_param("isss", $amount, $target_file, $transaction_code, $user_mobile);

            } else {
                // First-time purchase, insert new record
                $stmt = $conn->prepare("INSERT INTO myapp_payment (user_mobile_id, amount, screenshot, transaction_code, status) VALUES (?, ?, ?, ?, '0')");
                $stmt->bind_param("siss", $user_mobile, $amount, $target_file, $transaction_code);

            }

            if ($stmt->execute()) {
                echo "<script>alert('Payment screenshot uploaded successfully!'); window.location.href='./';</script>";
            } else {
                echo "<script>alert('Database error: " . addslashes($stmt->error) . "'); window.history.back();</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Error moving file. Check folder permissions.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('File upload error: " . $_FILES["payment_screenshot"]["error"] . "'); window.history.back();</script>";
    }
}
?>
