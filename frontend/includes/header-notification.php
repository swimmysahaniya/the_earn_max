<?php
//session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

include("includes/config.php");

// Get logged-in user mobile number
$user_mobile = $_SESSION["user_mobile"];

// Fetch the latest successful referral payment for the logged-in user
$query = "SELECT user_mobile_id, amount FROM myapp_payment WHERE user_mobile_id = ? ORDER BY id DESC LIMIT 1";
// $query = "SELECT user_mobile, amount FROM myapp_payment WHERE user_mobile = ? AND status = 1 ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $amount = $row['amount'];

    // Mask the mobile number (show only last 6 digits)
    $masked_mobile = "******" . substr($user_mobile, -4);

    // Referral reward (assuming 10% of the amount)
    $reward_amount = $amount;
    ?>
    <!-- Notification -->
    <div class="bg-light text-center py-2 mt-1" style="border-top:1px solid #ffffff24">
        <p class="mb-0" style="color: #fff; font-weight:600">
            🎉 Congratulations to <?= $masked_mobile; ?> for referring a membership and receiving INR <?= number_format($reward_amount, 2); ?>!
            | <a href="membership.php" class="link-flash">Upgrade membership</a>
        </p>
    </div>
    <?php
} else {
    ?>
    <!-- Message if no membership is taken -->
    <div class="bg-light text-center py-2 mt-1" style="border-top:1px solid #ffffff24">
        <p class="mb-0" style="color: #fff; font-weight:600">
            ⚠️ Please take a membership to start earning rewards.  | <a href="membership.php" class="link-flash">Purchase membership</a>
        </p>
    </div>
    <?php
}
?>
