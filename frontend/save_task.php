<?php
session_start();
include("includes/config.php");

$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

$task_id = $_POST['task_id'] ?? 0;
$completed_tasks = $_POST['completed_tasks'] ?? 0;
$total_earnings = $_POST['total_earnings'] ?? 0;
$video_url = $_POST['video_url'] ?? '';

$task_id = intval($task_id);
$completed_tasks = intval($completed_tasks);
$total_earnings = intval($total_earnings);

if ($task_id <= 0 || $completed_tasks <= 0 || $total_earnings < 0 || empty($video_url)) {
    echo json_encode(["status" => "error", "message" => "Invalid input data"]);
    exit;
}

$conn->begin_transaction();

// Insert into watched videos
$query1 = "INSERT INTO myapp_watchedvideo (user_mobile_id, task_id, video_url, watched_at)
          VALUES (?, ?, ?, NOW())
          ON DUPLICATE KEY UPDATE watched_at = NOW()";

$stmt1 = $conn->prepare($query1);
$stmt1->bind_param("sss", $user_mobile, $task_id, $video_url);
$stmt1->execute();

// Insert into completed tasks
$query2 = "INSERT INTO myapp_completedtask (user_mobile, completed_tasks, total_earnings, task_id, date)
           VALUES (?, ?, ?, ?, CURDATE())
           ON DUPLICATE KEY UPDATE completed_tasks = completed_tasks + ?, total_earnings = total_earnings + ?";

$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("siiiii", $user_mobile, $completed_tasks, $total_earnings, $task_id, $completed_tasks, $total_earnings);
$stmt2->execute();

$conn->commit();
echo json_encode(["status" => "success", "message" => "Task saved successfully"]);
?>
