<?php
session_start();
include("includes/config.php"); // Ensure database connection

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

// Fetch users data from Django API
$users_api_url = "http://127.0.0.1:8000/api/users/";
$users_data = json_decode(file_get_contents($users_api_url), true);

// Fetch approved payments from Django API
$payments_api_url = "http://127.0.0.1:8000/api/payments/";
$payments_data = json_decode(file_get_contents($payments_api_url), true);

/***************************************************** joining rule start **************************************/

// Get logged-in user's investment
/* $user_payment = null;
foreach ($payments_data as $payment) {
    if ($payment['user_mobile'] == $user_mobile && $payment['status'] == '1') {
        $user_payment = $payment;
        break;
    }
}

if ($user_payment) {
    $investment_amount = $user_payment['amount'];

    // Fetch logged-in user's referral code
    $user_referral_code = null;
    foreach ($users_data as $user) {
        if ($user['mobile'] == $user_mobile) { // Find the logged-in user's data
            $user_referral_code = $user['referral_code'];
            break;
        }
    }

    // Debugging (Optional: Remove after testing)
    if (!$user_referral_code) {
        die("Referral code not found for the logged-in user.");
    }

    // Find all users referred by the logged-in user
    $referred_users = [];
    foreach ($users_data as $user) {
        if ($user['invited_by'] == $user_referral_code) { // Fetch users referred by logged-in user
            $referred_users[] = $user['mobile'];
        }
    }

    if (!empty($referred_users)) {
        $total_referred_investment = 0;

        // Calculate total investment from referred users
        foreach ($payments_data as $payment) {
            if (in_array($payment['user_mobile'], $referred_users) && $payment['status'] == '1') {
                $total_referred_investment += $payment['amount'];
            }
        }

        if ($total_referred_investment > 0) {
            // Calculate Refund Amount
            $refund_amount = min($investment_amount, $total_referred_investment);

            $conn->begin_transaction();

            // Lock the row for update to prevent duplicate claims
            $check_refund_query = $conn->prepare("SELECT COUNT(*), SUM(refunded_amount) FROM myapp_refund WHERE user_mobile_id = ? FOR UPDATE");
            $check_refund_query->bind_param("s", $user_mobile);
            $check_refund_query->execute();
            $check_refund_query->store_result();
            $check_refund_query->bind_result($existing_count, $total_refunded);
            $check_refund_query->fetch();

            if ($total_refunded < $investment_amount && $existing_count == 0) {
                // Insert refund for the logged-in user based on referred users' investment
                $stmt = $conn->prepare("INSERT INTO myapp_refund (user_mobile_id, refunded_amount, status, created_at) VALUES (?, ?, '0', NOW())");
                $stmt->bind_param("sd", $user_mobile, $refund_amount);
                $stmt->execute();
                $stmt->close();
                $conn->commit(); // Commit transaction
            } else {
                $conn->rollback(); // Undo transaction if refund is not needed
            }

            $check_refund_query->close();
        }
    }
} */

/***************************************************** joining rule end **************************************/

/***************************************************** monthly income start **************************************/

// Check if user has received a full refund
$refund_query = $conn->prepare("
    SELECT COALESCE(SUM(refunded_amount), 0)
    FROM myapp_refund
    WHERE user_mobile_id = ? AND status = '1'
");
$refund_query->bind_param("s", $user_mobile);
$refund_query->execute();
$refund_query->store_result();
$refund_query->bind_result($refunded_amount);
$refund_query->fetch();
$refund_query->close();

$has_full_refund = ($refunded_amount > 0);
//print_r($has_full_refund);die;
if ($has_full_refund) {
    $new_referrals = [];

    // Fetch the logged-in user's referral code in one step
    $referral_code = null;
    foreach ($users_data as $user) {
        if ($user['mobile'] == $user_mobile) {
            $referral_code = $user['referral_code'];
            break;
        }
    }
//print_r($referral_code);die;
    // Initialize referral amount
    $total_new_investment = 0;

    if (!empty($referral_code)) {
        // Step 1: Fetch last 5 referred users
        $referred_users = [];
        $referral_stmt = $conn->prepare("
            SELECT mobile
            FROM myapp_users
            WHERE invited_by = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $referral_stmt->bind_param("s", $referral_code);
        $referral_stmt->execute();
        $result = $referral_stmt->get_result();
//print_r($result->fetch_assoc());die;
        while ($row = $result->fetch_assoc()) {
            $referred_users[] = $row['mobile'];
        }
        $referral_stmt->close();
//print_r($referred_users);die;
        // Step 2: Fetch their total approved payments
        if (!empty($referred_users)) {
            $placeholders = implode(',', array_fill(0, count($referred_users), '?'));
            $query = "
                SELECT SUM(amount) AS total_new_investment
                FROM myapp_payment
                WHERE user_mobile_id IN ($placeholders)
                AND status = '1'
            ";

            // Prepare statement dynamically
            $payment_stmt = $conn->prepare($query);
            $types = str_repeat('s', count($referred_users));
            $payment_stmt->bind_param($types, ...$referred_users);
            $payment_stmt->execute();
            $payment_stmt->bind_result($total_new_investment);
            $payment_stmt->fetch();
            $payment_stmt->close();
        }
    }

    // Step 3: Compute 10% monthly income if applicable
    if ($total_new_investment > 0) {
        $monthly_income = $total_new_investment * 0.10;
//print_r($total_new_investment);die;
        // Check if income is already recorded for this month
        $income_check = $conn->prepare("
            SELECT COUNT(*)
            FROM myapp_monthlyincome
            WHERE user_mobile_id = ?
            AND MONTH(created_at) = MONTH(NOW())
        ");
        $income_check->bind_param("s", $user_mobile);
        $income_check->execute();
        $income_check->store_result();
        $income_check->bind_result($income_count);
        $income_check->fetch();
        $income_check->close();

        if ($income_count == 0) {
            // Insert new monthly income record
            $income_query = $conn->prepare("
                INSERT INTO myapp_monthlyincome
                (user_mobile_id, total_referred_investment, monthly_income, status, created_at, updated_at)
                VALUES (?, ?, ?, '0', NOW(), NOW())
            ");
            $income_query->bind_param("sdd", $user_mobile, $total_new_investment, $monthly_income);
            $income_query->execute();
            $income_query->close();
        }
    }
}

// Fetch monthly income for display
$income_list = [];
$income_query = $conn->prepare("SELECT * FROM myapp_monthlyincome WHERE user_mobile_id = ?");
$income_query->bind_param("s", $user_mobile);
$income_query->execute();
$result = $income_query->get_result();
while ($row = $result->fetch_assoc()) {
    $income_list[] = $row;
}
$income_query->close();

/***************************************************** monthly income end **************************************/

/***************************************************** ID Upgrade Rule **************************************/

// Get total earnings from tasks
$task_earnings_query = $conn->prepare("SELECT SUM(total_earnings) FROM myapp_completedtask WHERE user_mobile = ?");
$task_earnings_query->bind_param("s", $user_mobile);
$task_earnings_query->execute();
$task_earnings_query->store_result();
$task_earnings_query->bind_result($total_task_earnings);
$task_earnings_query->fetch();
$task_earnings_query->close();

// Count referrals made by the user
// Step 1: Fetch the referral code of the logged-in user
$referral_code = null;
foreach ($users_data as $user) {
    if ($user['mobile'] == $user_mobile) { // Find the logged-in user's referral code
        $referral_code = $user['referral_code'];
        break;
    }
}

// Step 2: Fetch the count of users who used this referral code
$referral_count = 0;
if (!empty($referral_code)) {
    $investment_query = $conn->prepare("SELECT COUNT(*) FROM myapp_users WHERE invited_by = ? AND status = 1");
    $investment_query->bind_param("s", $referral_code); // Bind referral code, NOT user_mobile
    $investment_query->execute();
    $investment_query->bind_result($referral_count);
    $investment_query->fetch();
    $investment_query->close();
}

// Now, $referral_count contains the correct number of users referred by the logged-in user.
//print_r($referral_count);die;
// Fetch user's initial investment
$investment_query = $conn->prepare("SELECT SUM(amount) FROM myapp_payment WHERE user_mobile_id = ? AND status = '1'");
$investment_query->bind_param("s", $user_mobile);
$investment_query->execute();
$investment_query->store_result();
$investment_query->bind_result($initial_investment);
$investment_query->fetch();
$investment_query->close();

// Check if upgrade conditions are met
$can_upgrade = ($referral_count >= 17) && ($total_task_earnings >= $initial_investment);
//print_r($can_upgrade);die;
if ($can_upgrade) {
    // Upgrade the user's ID (assuming we have a 'membership_level' field)
    $upgrade_query = $conn->prepare("UPDATE myapp_users SET membership_level = membership_level + 1 WHERE mobile = ?");
    $upgrade_query->bind_param("s", $user_mobile);
    $upgrade_query->execute();
    $upgrade_query->close();
}

/***************************************************** ID Upgrade Rule End **************************************/

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">🚀 Rules & Guidelines</h2>

    <ul class="nav nav-pills mb-3 justify-content-center" id="rulesTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="joining-tab" data-bs-toggle="pill" data-bs-target="#joining" type="button" role="tab">Joining Rule</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="income-tab" data-bs-toggle="pill" data-bs-target="#income" type="button" role="tab">Monthly Income</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="upgrade-tab" data-bs-toggle="pill" data-bs-target="#upgrade" type="button" role="tab">ID Upgrade</button>
        </li>
    </ul>

    <div class="tab-content" id="rulesTabContent">
        <!-- Joining Rule -->
        <div class="tab-pane fade show active" id="joining" role="tabpanel">
            <div class="card p-4">
                <h5 class="card-title"><i class="icon bi bi-person-plus"></i> Joining Rule</h5>
                <p>✅ Refer <strong>1 new member</strong> and get <strong>100% refund</strong> of your initial investment.</p>
                <p>⚠️ If the referred member invests <strong>less</strong>, only that amount is refunded.</p>
                <p>🔄 To recover the full investment, refer more members.</p>
            </div>
        </div>

        <!-- Monthly Income Rule -->
        <div class="tab-pane fade" id="income" role="tabpanel">
            <div class="card p-4">
                <h5 class="card-title"><i class="icon bi bi-cash"></i> Monthly Income Rule</h5>
                <p>✅ After receiving a <strong>full refund</strong>, refer <strong>5 new members</strong> to earn <strong>10% monthly income</strong> on total new investment.</p>
                <p>📈 The more referrals you make, the higher your earnings!</p>
            </div>
        </div>

        <!-- ID Upgrade Rule -->
        <div class="tab-pane fade" id="upgrade" role="tabpanel">
            <div class="card p-4">
                <h5 class="card-title"><i class="icon bi bi-arrow-up-circle"></i> ID Upgrade Rule</h5>
                <p>✅ When you refer <strong>11 new members</strong>, your <strong>task earnings increase</strong>, provided your earnings match the initial investment.</p>
                <p>🚀 Keep referring to unlock higher benefits!</p>

                <p><strong>Your Referrals:</strong> <?php echo $referral_count; ?>/11</p>
                <p><strong>Your Task Earnings:</strong> ₹ <?php echo $total_task_earnings; ?></p>
                <p><strong>Your Total Investment:</strong> ₹ <?php echo $initial_investment; ?></p>

                <?php if ($can_upgrade) { ?>
                    <p class="text-success"><strong>🎉 Congratulations! Your ID has been upgraded.</strong></p>
                <?php } else { ?>
                    <p class="text-danger">📌 You need at least 11 referrals and earnings equal to your initial investment.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="text-center">📈 Your Monthly Income</h2>
        <?php if (!empty($income_list)) { ?>
            <table class="table">
                <tr>
                    <th>Total Referred Investment</th>
                    <th>Monthly Income</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($income_list as $income) { ?>
                    <tr>
                        <td><?php echo "₹" . $income['total_referred_investment']; ?></td>
                        <td><?php echo "₹" . $income['monthly_income']; ?></td>
                        <td>
                            <?php
                                if ($income['status'] == '0') {
                                    echo 'Pending';
                                } elseif ($income['status'] == '1') {
                                    echo 'Approved';
                                } else {
                                    echo 'Rejected';
                                }
                            ?>
                        </td>
                        <td><?php echo $income['created_at']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="text-center">No monthly income yet. Keep referring!</p>
        <?php } ?>
    </div>

    <!-- Progress Tracker -->
    <div class="mt-4">
        <h5>📊 Referral Progress</h5>
        <div class="progress">
            <div class="progress-bar bg-success" role="progressbar" style="width: 40%;" aria-valuenow="4" aria-valuemin="0" aria-valuemax="11">
                4/11 Referrals
            </div>
        </div>
    </div>
</div>

<script>
    // JavaScript to update progress dynamically (if needed)
    document.addEventListener("DOMContentLoaded", function () {
        let referrals = <?php echo $referral_count; ?>; // Change this dynamically as per user data
        let maxReferrals = 11;
        let progressBar = document.querySelector(".progress-bar");
        let progressPercentage = (referrals / maxReferrals) * 100;
        progressBar.style.width = progressPercentage + "%";
        progressBar.innerHTML = referrals + "/11 Referrals";
    });
</script>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
