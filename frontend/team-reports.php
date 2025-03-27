<?php
session_start();
include("includes/config.php"); // Ensure database connection

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    header("Location: login.php");
    exit();
}

// Fetch user_referral_code
$query = "SELECT referral_code FROM myapp_users WHERE mobile = '$user_mobile' AND status = '1'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$user_referral_code = $row['referral_code'] ?? 0;

// Recursive function to get referral hierarchy
function getReferralChain($conn, $referral_code, $level = 1) {
    $query = "SELECT u.mobile, u.created_at, u.referral_code, p.amount, p.status
              FROM myapp_users u
              LEFT JOIN myapp_payment p ON u.mobile = p.user_mobile_id
              WHERE u.invited_by = '$referral_code'";
    $result = mysqli_query($conn, $query);

    $referrals = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['level'] = $level;
        $referrals[] = $row;

        // Fetch next level referrals
        $sub_referrals = getReferralChain($conn, $row['referral_code'], $level + 1);
        $referrals = array_merge($referrals, $sub_referrals);
    }
    return $referrals;
}

// Fetch referral chain
$referral_chain = getReferralChain($conn, $user_referral_code);

// Calculate Total Referred Investment (approved only)
$query = "SELECT SUM(p.amount) AS total_referred_investment
          FROM myapp_users u
          JOIN myapp_payment p ON u.mobile = p.user_mobile_id
          WHERE u.invited_by = '$user_referral_code' AND p.status = '1'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_referred_investment = $row['total_referred_investment'] ?? 0;

// Count total referrals
$query = "SELECT COUNT(id) AS total_referrals FROM myapp_users WHERE invited_by = '$user_referral_code'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$total_referrals = $row['total_referrals'];
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2>Team Report</h2>

    <div class="box"><strong>Total Referred Investment:</strong> ₹<?php echo number_format($total_referred_investment, 2); ?></div>
    <div class="box"><strong>Total Referrals:</strong> <?php echo $total_referrals; ?></div>

    <h3>Referral Chain Overview</h3>
    <table class="table">
        <tr>
            <th>Level</th>
            <th>Mobile</th>
            <th>Joined Date</th>
            <th>Investment</th>
            <th>Status</th>
        </tr>
        <?php foreach ($referral_chain as $referral) {
            // Create visual indentation based on level
            $indentation = str_repeat("→ ", $referral['level'] - 1);

            // Mask the mobile number (show only last 6 digits)
            $masked_mobile = "******" . substr($referral['mobile'], -4);
        ?>
        <tr>
            <td>Level <?php echo $referral['level']; ?></td>
            <td><?php echo $indentation . $masked_mobile; ?></td>
            <td><?php echo date('d-m-Y', strtotime($referral['created_at'])); ?></td>
            <td>₹ <?php echo number_format((float) ($referral['amount'] ?? 0), 2); ?></td>
            <td>
                <?php
                    if ($referral['status'] == '2') {
                        echo 'Rejected';
                    } elseif ($referral['status'] == '1') {
                        echo 'Approved';
                    } else {
                        echo 'Pending';
                    }
                ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
