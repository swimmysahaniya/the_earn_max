<?php
session_start();
include("includes/config.php"); // Database connection

// Fetch leaderboard based on Total Earnings
$earning_leaderboard = $conn->query("
SELECT
    u.mobile,
    COALESCE(monthly_income.total, 0) AS monthly_income,
    COALESCE(task_earnings.total, 0) AS task_earnings,
    COALESCE(extra_earnings.total, 0) AS extra_earnings,
    COALESCE(referral_earnings.total, 0) AS referral_earnings,
    COALESCE(total_refunds.total, 0) AS total_refunds,
    COALESCE(total_withdrawals.total, 0) AS total_withdrawals,
    (
        COALESCE(monthly_income.total, 0) +
        COALESCE(task_earnings.total, 0) +
        COALESCE(extra_earnings.total, 0) +
        COALESCE(referral_earnings.total, 0) +
        COALESCE(total_refunds.total, 0)
    ) AS total_earnings,
    (
        (
            COALESCE(monthly_income.total, 0) +
            COALESCE(task_earnings.total, 0) +
            COALESCE(extra_earnings.total, 0) +
            COALESCE(referral_earnings.total, 0) +
            COALESCE(total_refunds.total, 0)
        ) - COALESCE(total_withdrawals.total, 0)
    ) AS net_earnings
FROM app.myapp_users u
LEFT JOIN (
    SELECT user_mobile_id, SUM(monthly_income) AS total
    FROM app.myapp_monthlyincome
    GROUP BY user_mobile_id
) AS monthly_income ON u.mobile = monthly_income.user_mobile_id
LEFT JOIN (
    SELECT user_mobile, SUM(total_earnings) AS total
    FROM app.myapp_completedtask
    GROUP BY user_mobile
) AS task_earnings ON u.mobile = task_earnings.user_mobile
LEFT JOIN (
    SELECT user_mobile_id, SUM(extra_amount) AS total
    FROM app.myapp_extraincome
    GROUP BY user_mobile_id
) AS extra_earnings ON u.mobile = extra_earnings.user_mobile_id
LEFT JOIN (
    SELECT user_mobile_id, SUM(refunded_amount) AS total
    FROM app.myapp_refund
    GROUP BY user_mobile_id
) AS total_refunds ON u.mobile = total_refunds.user_mobile_id
LEFT JOIN (
    SELECT user_mobile_id, SUM(withdrawal_amount) AS total
    FROM app.myapp_withdrawal
    WHERE status = '1'
    GROUP BY user_mobile_id
) AS total_withdrawals ON u.mobile = total_withdrawals.user_mobile_id
LEFT JOIN (
    SELECT u.invited_by, SUM(p.amount) * 0.10 AS total
    FROM app.myapp_payment p
    JOIN app.myapp_users u ON p.user_mobile_id = u.mobile
    WHERE p.status = '1'
    GROUP BY u.invited_by
) AS referral_earnings ON u.referral_code = referral_earnings.invited_by
ORDER BY net_earnings DESC
LIMIT 10
");

// Fetch leaderboard based on Total Referrals
$referral_leaderboard = $conn->query("
    SELECT u.mobile, COUNT(r.mobile) AS referral_count
    FROM myapp_users u
    LEFT JOIN myapp_users r ON u.referral_code = r.invited_by
    GROUP BY u.mobile
    ORDER BY referral_count DESC
    LIMIT 10
");

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center">🏆 Leaderboard</h2>

    <!-- Total Earnings Leaderboard -->
    <div class="row mt-4">
        <div class="col-md-6">
            <h3>Total Earnings Leaderboard</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Mobile</th>
                        <th>Total Earnings (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    while ($row = $earning_leaderboard->fetch_assoc()) {

                        // Mask the mobile number (show only last 6 digits)
                        $masked_mobile = "******" . substr($row['mobile'], -4);

                        echo "<tr>
                                <td>{$rank}</td>
                                <td>{$masked_mobile}</td>
                                <td>₹ " . number_format($row['net_earnings'], 2) . "</td>
                              </tr>";
                        $rank++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Total Referrals Leaderboard -->
        <div class="col-md-6">
            <h3>Total Referrals Leaderboard</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Mobile</th>
                        <th>Referral Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    while ($row = $referral_leaderboard->fetch_assoc()) {

                        // Mask the mobile number (show only last 6 digits)
                        $masked_mobile = "******" . substr($row['mobile'], -4);

                        echo "<tr>
                                <td>{$rank}</td>
                                <td>{$masked_mobile}</td>
                                <td>{$row['referral_count']}</td>
                              </tr>";
                        $rank++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
