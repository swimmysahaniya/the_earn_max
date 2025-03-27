<?php
include("includes/config.php"); // Ensure database connection
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

// Function to apply extra earning incentive
function applyExtraEarning($conn, $user_mobile) {
    // Fetch the user's joining date
    $stmt = $conn->prepare("SELECT created_at FROM myapp_users WHERE mobile = ?");
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $stmt->bind_result($joining_date);
    $stmt->fetch();
    $stmt->close();
//print_r($joining_date);die;
    if (!$joining_date) {
        return; // User not found
    }

    // Calculate the date 6 months after joining
    $six_months_after_joining = date('Y-m-d H:i:s', strtotime($joining_date . ' +6 months'));
//print_r($six_months_after_joining);die;
    // Fetch the user's first withdrawal date
    $stmt = $conn->prepare("
        SELECT MIN(created_at) FROM myapp_withdrawal
        WHERE user_mobile_id = ?
        AND status = '1'
    ");
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $stmt->bind_result($first_withdrawal_date);
    $stmt->fetch();
    $stmt->close();
//print_r($first_withdrawal_date);die;
    // Apply extra earning if:
    // - The user has never withdrawn (first_withdrawal_date is NULL)
    // - OR the first withdrawal happened after 6 months
    if ($first_withdrawal_date < $six_months_after_joining) {
        return; // Withdrawn before 6 months → No extra earning
    }

    // Calculate total earnings (monthly + task + refund)
    $stmt = $conn->prepare("
        SELECT
            (SELECT COALESCE(SUM(monthly_income), 0)
             FROM app.myapp_monthlyincome
             WHERE user_mobile_id = ? AND status = '1') AS monthly_income,

            (SELECT COALESCE(SUM(total_earnings), 0)
             FROM app.myapp_completedtask
             WHERE user_mobile = ?) AS task_earnings,

            (SELECT COALESCE(SUM(refunded_amount), 0)
             FROM app.myapp_refund
             WHERE user_mobile_id = ? AND status = '1') AS total_refunds,

            (COALESCE(
                (SELECT SUM(monthly_income)
                 FROM app.myapp_monthlyincome
                 WHERE user_mobile_id = ? AND status = '1'), 0)
             +
             COALESCE(
                (SELECT SUM(total_earnings)
                 FROM app.myapp_completedtask
                 WHERE user_mobile = ?), 0)
             +
             COALESCE(
                (SELECT SUM(refunded_amount)
                 FROM app.myapp_refund
                 WHERE user_mobile_id = ? AND status = '1'), 0)
            ) AS total_earnings
        ");
    $stmt->bind_param("ssssss", $user_mobile, $user_mobile, $user_mobile, $user_mobile, $user_mobile, $user_mobile);
    $stmt->execute();
    $stmt->bind_result($monthly_income, $task_earnings, $total_refunds, $total_earnings);
    $stmt->fetch();
    $stmt->close();

    if ($total_earnings === NULL) {
        return; // No earnings, exit function
    }
//print_r($total_earnings);die;
    // Calculate extra income (10% incentive)
    $extra_earning = $total_earnings * 0.10;
//print_r($extra_earning);die;
    // Check if extra income already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM myapp_extraincome WHERE user_mobile_id = ?");
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $stmt->bind_result($extra_income_count);
    $stmt->fetch();
    $stmt->close();

    if ($extra_income_count == 0 && $extra_earning > 0) {
        // Insert extra income if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO myapp_extraincome (user_mobile_id, extra_amount, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("sd", $user_mobile, $extra_earning);
        $stmt->execute();
        $stmt->close();
    }
}

function updateWalletBalance($conn, $user_mobile, $withdraw_amount = 0) {
    // Calculate total earnings including extra income
    $stmt = $conn->prepare("
        SELECT
            COALESCE((
                SELECT SUM(DISTINCT m.monthly_income)
                FROM myapp_monthlyincome m
                WHERE m.user_mobile_id = u.mobile AND m.status = '1'
            ), 0) +
            COALESCE((
                SELECT SUM(DISTINCT ct.total_earnings)
                FROM myapp_completedtask ct
                WHERE ct.user_mobile = u.mobile
            ), 0) +
            COALESCE((
                SELECT SUM(DISTINCT e.extra_amount)
                FROM myapp_extraincome e
                WHERE e.user_mobile_id = u.mobile
            ), 0) -
            COALESCE((
                SELECT SUM(DISTINCT w.withdrawal_amount)
                FROM myapp_withdrawal w
                WHERE w.user_mobile_id = u.mobile AND w.status = '1'
            ), 0) +
            COALESCE((
                SELECT SUM(DISTINCT r.refunded_amount)
                FROM myapp_refund r
                WHERE r.user_mobile_id = u.mobile AND r.status = '1'
            ), 0) AS total_balance
        FROM myapp_users u
        WHERE u.mobile = ?;
    ");
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $stmt->bind_result($total_balance);
    $stmt->fetch();
    $stmt->close();
//print_r($total_balance);die;
    if ($total_balance === NULL) {
        $total_balance = 0.00; // Prevent NULL balance
    }

    // Check if wallet exists
    $stmt = $conn->prepare("SELECT balance, total_withdrawn FROM myapp_wallet WHERE user_mobile_id = ?");
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $stmt->bind_result($wallet_balance, $total_withdrawn);
    $wallet_exists = $stmt->fetch();
    $stmt->close();
//print_r($wallet_balance);
//print_r($total_withdrawn);die;
    if (!$wallet_exists) {
        // If wallet does not exist, create a new one
        $wallet_balance = $total_balance;
        $total_withdrawn = 0.00;
        $stmt = $conn->prepare("
            INSERT INTO myapp_wallet (user_mobile_id, balance, total_withdrawn, status, updated_at)
            VALUES (?, ?, 0.00, '0', NOW())
        ");
        $stmt->bind_param("sd", $user_mobile, $wallet_balance);
        $stmt->execute();
        $stmt->close();
    } else {
        // Instead of overwriting, we check the difference and update accordingly
        if ($wallet_balance != $total_balance) {
            $stmt = $conn->prepare("
                UPDATE myapp_wallet
                SET balance = ?, updated_at = NOW()
                WHERE user_mobile_id = ?
            ");
            $stmt->bind_param("ds", $total_balance, $user_mobile);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Process Withdrawal (if any)
    if ($withdraw_amount > 0) {
        if ($wallet_balance >= $withdraw_amount) {
            $new_balance = $wallet_balance - $withdraw_amount;
            $new_total_withdrawn = $total_withdrawn + $withdraw_amount;

            $stmt = $conn->prepare("
                UPDATE myapp_wallet
                SET balance = ?, total_withdrawn = ?, updated_at = NOW()
                WHERE user_mobile_id = ?
            ");
            $stmt->bind_param("dds", $new_balance, $new_total_withdrawn, $user_mobile);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Insufficient balance!";
            return;
        }
    }
}

// Apply extra earning if applicable
applyExtraEarning($conn, $user_mobile);

// Update wallet balance before withdrawal
updateWalletBalance($conn, $user_mobile);

// Fetch user balance from the wallet
$stmt = $conn->prepare("SELECT balance FROM myapp_wallet WHERE user_mobile_id = ?");
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$stmt->bind_result($user_balance);
$stmt->fetch();
$stmt->close();
//print_r($user_balance);die;
if ($user_balance > 0) {
    $max_withdrawal = $user_balance * 0.5; // 50% of balance

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['withdrawal_amount'])) {
        $requested_amount = $_POST['withdrawal_amount'];

        if ($requested_amount > 0 && $requested_amount <= $max_withdrawal) {

            $tds_amount = $requested_amount * 0.1; // 10% TDS
            $final_amount = $requested_amount - $tds_amount; // Amount user receives
            //print_r("UPDATE myapp_wallet SET balance = balance - $requested_amount, total_withdrawn = total_withdrawn + $requested_amount, updated_at = NOW() WHERE user_mobile_id = '9598444111'");die;
            // Begin transaction for safe update
            $conn->begin_transaction();

            // Step 1: Update the wallet balance first
            $stmt = $conn->prepare("
                UPDATE myapp_wallet
                SET balance = balance - ?, total_withdrawn = total_withdrawn + ?, updated_at = NOW()
                WHERE user_mobile_id = ?
            ");
            $stmt->bind_param("dds", $requested_amount, $requested_amount, $user_mobile);
            $stmt->execute();
            $stmt->close();

            // Step 2: Force MySQL to refresh data before fetching the updated balance
            $conn->commit();  // ✅ Commit first update to reflect changes immediately

            // Step 4: Insert the withdrawal request with the correct balance
            $stmt = $conn->prepare("INSERT INTO myapp_withdrawal (user_mobile_id, withdrawal_amount, tds_amount, final_amount, status, created_at) VALUES (?, ?, ?, ?, '0', NOW())");
            $stmt->bind_param("sddd", $user_mobile, $requested_amount, $tds_amount, $final_amount);
            $stmt->execute();
            $stmt->close();

            // Step 5: Final commit to ensure everything is saved
            $conn->commit();
            $conn->close();

            // Redirect
            echo "<script>alert('Withdrawal request submitted successfully. Processing time: 3 working days.');</script>";
            echo "<script>window.location.href='withdraw.php';</script>";
        }
    }
} else {
    echo "<script>alert('Insufficient balance for withdrawal.');</script>";
}
?>
<?php
// Force fresh DB fetch every time page loads
$query = $conn->prepare("SELECT balance FROM myapp_wallet WHERE user_mobile_id = ?");
$query->bind_param("s", $user_mobile);
$query->execute();
$query->bind_result($db_balance);
$query->fetch();
$query->close();




//total balance show
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

$total_wallet = $task_earnings + $monthly_income + $total_refund + $total_extra_amount - $total_withdrawal_amount;
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center">Withdraw Funds</h2>
    <p>Available Balance: Rs. <?php echo number_format($total_wallet, 2); ?></p>
    <form method="post">
        <label>Enter Amount to Withdraw:</label>
        <input type="number" name="withdrawal_amount" step="0.01" max="<?php echo $db_balance * 0.5; ?>" required>
        <button type="submit">Request Withdrawal</button>
    </form>
</div>

<?php // Fetch refunds for display
$withdrawals = [];
$withdraw_query = $conn->prepare("SELECT * FROM myapp_withdrawal WHERE user_mobile_id = ?");
$withdraw_query->bind_param("s", $user_mobile);
$withdraw_query->execute();
$result = $withdraw_query->get_result();
while ($row = $result->fetch_assoc()) {
    $withdrawals[] = $row;
}
$withdraw_query->close();
?>

<div class="container mt-5">
    <h2 class="text-center">📈 Your Withdrawals</h2>
    <?php if (!empty($withdrawals)) { ?>
        <table class="table">
            <tr>
                <th>Withdrawal Amount</th>
                <th>TDS Amount</th>
                <th>Final Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php foreach ($withdrawals as $withdrawal) { ?>
                <tr>
                    <td><?php echo $withdrawal['withdrawal_amount']; ?></td>
                    <td><?php echo $withdrawal['tds_amount']; ?></td>
                    <td><?php echo $withdrawal['final_amount']; ?></td>
                    <td>
                        <?php
                            if ($withdrawal['status'] == '0') {
                                echo 'Pending';
                            } elseif ($withdrawal['status'] == '1') {
                                echo 'Approved';
                            } else {
                                echo 'Rejected';
                            }
                        ?>
                    </td>
                    <td><?php echo $withdrawal['created_at']; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="text-center">No withdrawal available.</p>
    <?php } ?>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>

