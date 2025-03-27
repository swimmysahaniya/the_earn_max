<?php
session_start();
include("includes/config.php"); // Ensure database connection

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

// Mask the mobile number (show only last 6 digits)
$masked_mobile = "******" . substr($user_mobile, -4);

// Fetch user profile
$query = "SELECT name, profile_image FROM myapp_profile WHERE user_mobile_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $name = $row['name'];
    $profile_image = $row['profile_image'] ?? "images/user-image-1.jpg"; // Default image
} else {
    $name = "Guest"; // Set a default name
    $profile_image = "images/user-image-1.jpg"; // Default image
}

// Fetch total earnings and per-task earnings
$stmt = $conn->prepare("
    SELECT t.task_number, t.title, SUM(ct.total_earnings) AS earnings
    FROM myapp_completedtask ct
    INNER JOIN myapp_task t ON ct.task_id = t.id
    WHERE ct.user_mobile = ?
    GROUP BY t.task_number, t.title
");
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();

// Store earnings in an associative array
$earnings = [];
while ($row = $result->fetch_assoc()) {
    $earnings[$row['task_number']] = $row['earnings'];
}

// Fetch total earnings
$total_earnings = array_sum($earnings);
?>
<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="wallet-container text-center">
  <div class="mb-4">
    <img src="<?php echo $profile_image; ?>" alt="<?php echo $name; ?>" class="rounded-circle" width="60">
    <p class="mt-2"><?php echo $masked_mobile; ?></p>
  </div>
  <div class="balance mb-4">Total Task Earnings (INR): <span><?php echo $total_earnings; ?></span></div>

  <div class="row">
    <?php
    // Display earnings for tasks 1 to 8
    for ($i = 1; $i <= 8; $i++) {
        $taskEarnings = isset($earnings[$i]) ? $earnings[$i] : 0;
        echo '
        <div class="col-6">
          <div class="card p-2">
            <h6>Task ' . $i . ' Earnings</h6>
            <p>' . $taskEarnings . '</p>
          </div>
        </div>';
    }
    ?>
  </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
