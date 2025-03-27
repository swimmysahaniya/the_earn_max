<?php
session_start();
include("includes/config.php"); // Ensure database connection

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];

// Fetch refunds for display
$refunds = [];
$refund_query = $conn->prepare("SELECT * FROM myapp_refund WHERE user_mobile_id = ?");
$refund_query->bind_param("s", $user_mobile);
$refund_query->execute();
$result = $refund_query->get_result();
while ($row = $result->fetch_assoc()) {
    $refunds[] = $row;
}
$refund_query->close();

?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center">📈 Your Refunds</h2>
    <?php if (!empty($refunds)) { ?>
        <table class="table">
            <tr>
                <th>Refund Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php foreach ($refunds as $refund) { ?>
                <tr>
                    <td><?php echo $refund['refunded_amount']; ?></td>
                    <td>
                        <?php
                            if ($refund['status'] == '0') {
                                echo 'Pending';
                            } elseif ($refund['status'] == '1') {
                                echo 'Approved';
                            } else {
                                echo 'Rejected';
                            }
                        ?>
                    </td>
                    <td><?php echo $refund['created_at']; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="text-center">No refunds available.</p>
    <?php } ?>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
