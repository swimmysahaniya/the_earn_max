<?php
include("includes/config.php");
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

// Fetch user_referral_code
$query = "SELECT referral_code FROM myapp_users WHERE mobile = '$user_mobile' AND status = '1'"; // Only approved users
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$user_referral_code = $row['referral_code'] ?? 0;
//print_r($user_referral_code);die;

// Fetch earnings data
$query = "SELECT
            COALESCE(SUM(m.monthly_income), 0) AS monthly_income,
            COALESCE(SUM(ct.total_earnings), 0) AS task_earnings,
            COALESCE(SUM(e.extra_amount), 0) AS extra_earnings,
            COALESCE(SUM(r.refunded_amount), 0) AS total_refunds
          FROM myapp_users u
          LEFT JOIN myapp_monthlyincome m ON u.mobile = m.user_mobile_id
          LEFT JOIN myapp_completedtask ct ON u.mobile = ct.user_mobile
          LEFT JOIN myapp_extraincome e ON u.mobile = e.user_mobile_id
          LEFT JOIN myapp_refund r ON u.mobile = r.user_mobile_id
          WHERE u.mobile = ?";

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

// Total balance
$total_balance = $monthly_income + $task_earnings + $extra_earnings + $total_refunds - $total_withdrawal;

// Fetch yesterday & today's earnings
$yesterdayQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS yesterday_earnings
                   FROM myapp_completedtask ct
                   WHERE ct.user_mobile = ? AND DATE(ct.date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$stmt = $conn->prepare($yesterdayQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$yesterdayResult = $stmt->get_result()->fetch_assoc();
$yesterday_earnings = $yesterdayResult['yesterday_earnings'];

// Today's earnings
$todayQuery = "SELECT COALESCE(SUM(ct.total_earnings), 0) AS today_earnings
               FROM myapp_completedtask ct
               WHERE ct.user_mobile = ? AND DATE(ct.date) = CURDATE()";
$stmt = $conn->prepare($todayQuery);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$todayResult = $stmt->get_result()->fetch_assoc();
$today_earnings = $todayResult['today_earnings'];

// Total revenue
$total_revenue = $total_balance;

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

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="wallet-container text-center">
  <div class="mb-4">
    <img src="images/user-image-1.jpg" alt="Profile" class="rounded-circle" width="80">
    <p class="mt-2"><?php echo $user_mobile; ?></p>
  </div>
  <div class="balance mb-4">Balance (INR): <span><?php echo number_format($total_balance, 2); ?></span></div>

  <div class="row">
    <div class="col-6">
      <div class="card p-2">
        <h6>Yesterday's Earnings</h6>
        <p><?php echo number_format($yesterday_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Today's Earnings</h6>
        <p><?php echo number_format($today_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>This Month's Earnings</h6>
        <p><?php echo number_format($monthly_income, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>This Week's Earnings</h6>
        <p><?php echo number_format($task_earnings, 2); ?></p>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Revenue</h6>
        <p><?php echo number_format($total_revenue, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Refunds</h6>
        <p><?php echo number_format($total_refunds, 2); ?></p>
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
        <h6>Incentives</h6>
        <p><?php echo number_format($extra_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Task Earnings</h6>
        <p><?php echo number_format($task_earnings, 2); ?></p>
      </div>
    </div>
    <div class="col-6">
      <div class="card p-2">
        <h6>Total Withdrawal</h6>
        <p><?php echo number_format($total_withdrawal, 2); ?></p>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
