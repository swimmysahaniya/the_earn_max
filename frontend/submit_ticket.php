<?php
session_start();
include("includes/config.php"); // Include database connection

$user_mobile = $_SESSION["user_mobile"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_mobile = $_POST['user_mobile'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $ticket_id = rand(100000, 999999);

    // Validate input
    if (empty($user_mobile) || empty($subject) || empty($message)) {
        echo "All fields are required!";
        exit;
    }

    // Insert data into the support ticket table
    $sql = "INSERT INTO myapp_supportticket (user_mobile_id, ticket_id, subject, message, created_at, status)
            VALUES (?, ?, ?, ?, NOW(), 'open')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_mobile, $ticket_id, $subject, $message);

    if ($stmt->execute()) {
        echo "Support ticket submitted successfully!";
    } else {
        echo "Error submitting ticket. Please try again.";
    }

    $stmt->close();
}
?>
