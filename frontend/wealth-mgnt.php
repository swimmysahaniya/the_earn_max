<?php
include("includes/config.php");
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

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

// Fetch user_referral_code
$query = "SELECT referral_code FROM myapp_users WHERE mobile = '$user_mobile' AND status = '1'"; // Only approved users
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$user_referral_code = $row['referral_code'] ?? 0;
//print_r($user_referral_code);die;

// Fetch earnings data
$query = "SELECT
            (SELECT COALESCE(SUM(m.monthly_income), 0)
             FROM myapp_monthlyincome m
             WHERE m.user_mobile_id = u.mobile AND m.status = '1') AS monthly_income,

            (SELECT COALESCE(SUM(ct.total_earnings), 0)
             FROM myapp_completedtask ct
             WHERE ct.user_mobile = u.mobile) AS task_earnings,

            (SELECT COALESCE(SUM(e.extra_amount), 0)
             FROM myapp_extraincome e
             WHERE e.user_mobile_id = u.mobile) AS extra_earnings,

            (SELECT COALESCE(SUM(r.refunded_amount), 0)
             FROM myapp_refund r
             WHERE r.user_mobile_id = u.mobile AND r.status = '1') AS total_refunds

        FROM myapp_users u
        WHERE u.mobile = ?
        ";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Assign values
$monthly_income = $result['monthly_income'];
$task_earnings = $result['task_earnings'];
$extra_earnings = $result['extra_earnings'];
$total_refunds = $result['total_refunds'];

$w_query = "SELECT SUM(withdrawal_amount) AS total_withdrawal FROM myapp_withdrawal WHERE user_mobile_id = '$user_mobile' AND status = '1'"; // Only approved
$result1 = mysqli_query($conn, $w_query);
$row1 = mysqli_fetch_assoc($result1);
$total_withdrawal = $row1['total_withdrawal'] ?? 0;
//print_r($total_withdrawal);die;

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

// Total balance
$total_balance = $monthly_income + $task_earnings + $extra_earnings + $total_refunds - $total_withdrawal + $referral_earnings;

$bonus_income = $monthly_income + $total_refunds;

// Fetch yesterday & today's task earnings
$yesterdayQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS yesterday_earnings
                   FROM myapp_completedtask ct
                   WHERE ct.user_mobile = ? AND DATE(ct.date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$stmt = $conn->prepare($yesterdayQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$yesterdayResult = $stmt->get_result()->fetch_assoc();
$yesterday_earnings = $yesterdayResult['yesterday_earnings'];

// Today's task earnings
$todayQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS today_earnings
               FROM myapp_completedtask ct
               WHERE ct.user_mobile = ? AND DATE(ct.date) = CURDATE()";
$stmt = $conn->prepare($todayQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$todayResult = $stmt->get_result()->fetch_assoc();
$today_earnings = $todayResult['today_earnings'];

// this week's task earnings
$weeklyQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS weekly_earnings
                FROM myapp_completedtask ct
                WHERE ct.user_mobile = ?
                AND DATE(ct.date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$stmt = $conn->prepare($weeklyQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$weeklyResult = $stmt->get_result()->fetch_assoc();
$weekly_earnings = $weeklyResult['weekly_earnings'];

// this months's task earnings
$monthlyQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS monthly_earnings
                 FROM myapp_completedtask ct
                 WHERE ct.user_mobile = ?
                 AND DATE(ct.date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$stmt = $conn->prepare($monthlyQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$monthlyResult = $stmt->get_result()->fetch_assoc();
$monthly_earnings = $monthlyResult['monthly_earnings'];


// Total revenue
$total_revenue = $total_balance;

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="wallet-container text-center">
  <div class="mb-4">
    <img src="<?php echo $profile_image; ?>" alt="<?php echo $name; ?>" class="rounded-circle" width="60">
    <p class="mt-2"><?php echo $masked_mobile; ?></p>
  </div>
  <div class="balance mb-4">Balance (INR): <span><?php echo number_format($total_balance, 2); ?></span></div>
  <h4 style="color:red; text-align:center;">Note: You can withdraw 50% of the total amount, <br> if you withdraw 100% amount then your account will be deactivated.</h4>
  <br>
  <div class="row">
    <div class="col-6">
      <div class="card p-2">
        <h6>Yesterday's Task Earnings</h6>
        <p><?php echo number_format($yesterday_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Today's Task Earnings</h6>
        <p><?php echo number_format($today_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
      <h6>This Week's Task Earnings</h6>
        <p><?php echo number_format($weekly_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>This Month's Task Earnings</h6>
        <p><?php echo number_format($monthly_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Task Earnings</h6>
        <p><?php echo number_format($task_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Referral Earnings</h6>
        <p><?php echo number_format($referral_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Bonus Income</h6>
        <p><?php echo number_format($bonus_income, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Incentives</h6>
        <p><?php echo number_format($extra_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Withdrawal</h6>
        <p><?php echo number_format($total_withdrawal, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Revenue</h6>
        <p><?php echo number_format($total_revenue, 2); ?></p>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
