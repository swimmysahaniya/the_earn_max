<?php
session_start();
include("includes/config.php");

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

//print_r($_POST);die;

// Get data from AJAX request
$task_id = $_POST['task_id'] ?? 0;
$completed_tasks = $_POST['completed_tasks'] ?? 0;
$total_earnings = $_POST['total_earnings'] ?? 0;

// Ensure numeric values
$task_id = intval($task_id);
$completed_tasks = intval($completed_tasks);
$total_earnings = intval($total_earnings);

if ($task_id <= 0 || $completed_tasks <= 0 || $total_earnings < 0) {
    echo json_encode(["status" => "error", "message" => "Invalid input data"]);
    exit;
}

// Insert or update the completed tasks in the database
$query = "INSERT INTO app.`myapp_completedtask` (user_mobile, completed_tasks, total_earnings, task_id, date)
          VALUES (?, ?, ?, ?, CURDATE())
          ON DUPLICATE KEY UPDATE completed_tasks = completed_tasks + ?, total_earnings = total_earnings + ?";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database query preparation failed: " . $conn->error]);
    exit;
}

// Bind all required parameters
$stmt->bind_param("siiiii", $user_mobile, $completed_tasks, $total_earnings, $task_id, $completed_tasks, $total_earnings);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Task saved successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Execution failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
