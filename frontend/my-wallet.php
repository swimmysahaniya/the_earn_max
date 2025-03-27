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

// Fetch Task Earnings
$query = "SELECT SUM(total_earnings) AS task_earnings FROM myapp_completedtask WHERE user_mobile = '$user_mobile'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$task_earnings = $row['task_earnings'] ?? 0;

// Fetch Monthly Income
$query = "SELECT SUM(monthly_income) AS monthly_income FROM myapp_monthlyincome WHERE user_mobile_id = '$user_mobile' AND status = '1'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$monthly_income = $row['monthly_income'] ?? 0;

// Fetch Refund Balance
$query = "SELECT refunded_amount FROM myapp_refund WHERE user_mobile_id = '$user_mobile' AND status = '1'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_refund = $row['refunded_amount'] ?? 0;

// Fetch Extra Income Balance
$query = "SELECT extra_amount FROM myapp_extraincome WHERE user_mobile_id = '$user_mobile'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_extra_amount = $row['extra_amount'] ?? 0;

// Fetch Extra Income Balance
$query = "SELECT SUM(withdrawal_amount) AS total_withdrawal_amount, SUM(tds_amount) AS total_tds_amount, SUM(final_amount) AS total_final_amount FROM myapp_withdrawal WHERE user_mobile_id = '$user_mobile' AND status = '1'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_withdrawal_amount = $row['total_withdrawal_amount'] ?? 0;
$total_tds_amount = $row['total_tds_amount'] ?? 0;
$total_final_amount = $row['total_final_amount'] ?? 0;

// Fetch user_referral_code
$query = "SELECT referral_code FROM myapp_users WHERE mobile = '$user_mobile' AND status = '1'"; // Only approved users
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$user_referral_code = $row['referral_code'] ?? 0;
//print_r($user_referral_code);die;

// Referral Earnings
$referralQuery = "SELECT COALESCE(SUM(p.amount) * 0.10, 0) AS referral_earnings
                  FROM myapp_users u
                  JOIN myapp_payment p ON u.mobile = p.user_mobile_id
                  WHERE u.invited_by = ? AND p.status = '1'";
$stmt = $conn->prepare($referralQuery);
$stmt->bind_param("s", $user_referral_code);
$stmt->execute();
$referralResult = $stmt->get_result()->fetch_assoc();
$referral_earnings = $referralResult['referral_earnings'];

// Fetch Total Wallet Balance
$query = "SELECT balance FROM myapp_wallet WHERE user_mobile_id = '$user_mobile'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_wallet1 = $row['balance'] ?? 0;

$total_wallet = $task_earnings + $monthly_income + $total_refund + $total_extra_amount - $total_withdrawal_amount + $referral_earnings;
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="wallet-container text-center">
  <div class="mb-4">
    <img src="<?php echo $profile_image; ?>" alt="<?php echo $name; ?>" class="rounded-circle" width="60">
    <p class="mt-2"><?php echo $masked_mobile; ?></p>
  </div>
  <div class="balance mb-4">Total Balance (INR): <span><?php echo $total_wallet; ?></span></div>

  <div class="row">
    <!--- <div class="col-6">
      <div class="card p-2">
        <h6>Task Earnings</h6>
        <p>₹ <?php echo $task_earnings; ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Monthly Income</h6>
        <p>₹ <?php echo $monthly_income; ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Refund</h6>
        <p>₹ <?php echo $total_refund; ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Extra Income</h6>
        <p>₹ <?php echo $total_extra_amount; ?></p>
      </div>
    </div> --->
    <div class="col-4">
      <div class="card p-2">
        <h6>Total Withdrawal</h6>
        <p>₹ <?php echo $total_withdrawal_amount; ?></p>
      </div>
    </div>
    <div class="col-4">
      <div class="card p-2">
        <h6>TDS Amount</h6>
        <p>₹ <?php echo $total_tds_amount; ?></p>
      </div>
    </div>
    <div class="col-4">
      <div class="card p-2">
        <h6>Amount Withdrawn</h6>
        <p>₹ <?php echo $total_final_amount; ?></p>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
