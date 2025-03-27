<?php
include("includes/config.php"); // Ensure database connection
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

function getDailyStatement($user_mobile, $conn) {
    $sql = "SELECT
                COALESCE(SUM(p.amount), 0) AS total_investment,
                COALESCE(SUM(w.withdrawal_amount), 0) AS total_withdrawals,
                COALESCE(SUM(t.total_earnings), 0) AS total_task_earnings,
                COALESCE(SUM(m.monthly_income), 0) AS total_monthly_income,
                COALESCE(SUM(e.extra_amount), 0) AS total_extra_income,
                COALESCE(SUM(r.refunded_amount), 0) AS total_refunds,
                (
                    COALESCE(SUM(p.amount), 0) - COALESCE(SUM(w.withdrawal_amount), 0) +
                    COALESCE(SUM(t.total_earnings), 0) + COALESCE(SUM(m.monthly_income), 0) +
                    COALESCE(SUM(e.extra_amount), 0) + COALESCE(SUM(r.refunded_amount), 0)
                ) AS total_balance
            FROM myapp_users u
            LEFT JOIN myapp_payment p ON u.mobile = p.user_mobile_id AND DATE(p.created_at) = CURDATE() AND p.status = '1'
            LEFT JOIN myapp_withdrawal w ON u.mobile = w.user_mobile_id AND DATE(w.created_at) = CURDATE() AND w.status = '1'
            LEFT JOIN myapp_completedtask t ON u.mobile = t.user_mobile AND DATE(t.date) = CURDATE()
            LEFT JOIN myapp_monthlyincome m ON u.mobile = m.user_mobile_id AND DATE(m.created_at) = CURDATE() AND m.status = '1'
            LEFT JOIN myapp_extraincome e ON u.mobile = e.user_mobile_id AND DATE(e.created_at) = CURDATE()
            LEFT JOIN myapp_refund r ON u.mobile = r.user_mobile_id AND DATE(r.created_at) = CURDATE() AND r.status = '1'
            WHERE u.mobile = ?
            GROUP BY u.mobile";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_mobile);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result;
}

// Example Usage
//$user_mobile = $user_mobile; // Replace with actual user mobile
$daily_statement = getDailyStatement($user_mobile, $conn);
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<?php
echo "<div class='container mt-5'><h2 class='text-center'>Daily Statement for " . date('Y-m-d') . "</h2>";
echo "<table class='table'>
<tr>
    <th>Investment</th>
    <th>Task Earnings</th>
    <th>Monthly Income</th>
    <th>Extra Earnings</th>
    <th>Refunds</th>
    <th>Withdrawals</th>
    <th>Total Balance</th>
</tr>
<tr>
    <td>" . number_format($daily_statement['total_investment'], 2) . "</td>
    <td>" . number_format($daily_statement['total_task_earnings'], 2) . "</td>
    <td>" . number_format($daily_statement['total_monthly_income'], 2) . "</td>
    <td>" . number_format($daily_statement['total_extra_income'], 2) . "</td>
    <td>" . number_format($daily_statement['total_refunds'], 2) . "</td>
    <td>" . number_format($daily_statement['total_withdrawals'], 2) . "</td>
    <td><strong>" . number_format($daily_statement['total_balance'], 2) . "</strong></td>
</tr>
</table>
</div>";
?>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
