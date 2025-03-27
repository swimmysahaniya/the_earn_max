<?php
session_start();
if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$mobile = $_SESSION["user_mobile"];
$referral_code = $_SESSION["referral_code"]; // Fetch referral code from session
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>
<?php include("includes/header-notification.php"); ?>

<?php
// Check if the user is activated
$query = "SELECT status FROM myapp_users WHERE mobile = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$user_status = $result['status'];
?>


<?php if ($user_status == '1'): ?>
    <?php
    // Task Timings Logic
    date_default_timezone_set('Asia/Kolkata'); // Set the correct timezone
    $dayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
    $currentHour = date('H'); // Current hour in 24-hour format

    $taskAllowed = ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentHour >= 11 && $currentHour < 17);

    //////////////////////////refund logic start /////////////////////////////

    $conn->begin_transaction();

    // Step 1: Get user's total refunded amount
    $query = $conn->prepare("SELECT SUM(refunded_amount) FROM myapp_refund WHERE user_mobile_id = ?");
    $query->bind_param("s", $user_mobile);
    $query->execute();
    $query->bind_result($total_refunded);
    $query->fetch();
    $query->close();

    if (!$total_refunded) {
        $total_refunded = 0;
    }

    // Step 2: Get user's total investment
    $query = $conn->prepare("SELECT amount FROM myapp_payment WHERE user_mobile_id = ? AND status = '1' LIMIT 1");
    $query->bind_param("s", $user_mobile);
    $query->execute();
    $query->bind_result($investment_amount);
    $query->fetch();
    $query->close();

    if (!$investment_amount) {
        $investment_amount = 0;
    }

    // Step 3: Get total referred investment
    $query = $conn->prepare("
        SELECT SUM(p.amount)
        FROM myapp_payment p
        JOIN myapp_users u ON p.user_mobile_id = u.mobile
        WHERE u.invited_by = (SELECT referral_code FROM myapp_users WHERE mobile = ?)
        AND p.status = '1'
    ");
    $query->bind_param("s", $user_mobile);
    $query->execute();
    $query->bind_result($total_referred_investment);
    $query->fetch();
    $query->close();

    if (!$total_referred_investment) {
        $total_referred_investment = 0;
    }

    // Step 4: Calculate refundable amount
    $refund_amount = min($investment_amount, $total_referred_investment);

    // Step 5: Insert refund if applicable
    if ($refund_amount > 0 && $total_refunded < $investment_amount) {
        $query = $conn->prepare("INSERT INTO myapp_refund (user_mobile_id, refunded_amount, status, created_at) VALUES (?, ?, '0', NOW())");
        $query->bind_param("sd", $user_mobile, $refund_amount);
        $query->execute();
        $query->close();

        // Update refund_status to '1' in myapp_users
        $query = $conn->prepare("UPDATE myapp_users SET refund_status = '1' WHERE mobile = ?");
        $query->bind_param("s", $user_mobile);
        $query->execute();
        $query->close();

        $conn->commit(); // Commit transaction
    } else {
        $conn->rollback(); // Rollback if no refund is needed
    }

    //////////////////////refund logic end //////////////////////////////////
    ////////////////////support ticket start //////////////////////////////////
    ?>

    <!-- Button to Open the Modal -->
    <button type="button" class="btn btn-primary fixed-left-button" data-bs-toggle="modal" data-bs-target="#supportTicketModal">
        Support Ticket
    </button>

    <!-- Support Ticket Modal -->
    <div class="modal fade" id="supportTicketModal" tabindex="-1" aria-labelledby="supportTicketModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supportTicketModalLabel">Submit Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="supportTicketForm">
                        <div class="mb-3">
                            <label for="user_mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="user_mobile" name="user_mobile" value="<?php echo $user_mobile; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery for AJAX Submission -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#supportTicketForm").submit(function(event) {
                event.preventDefault(); // Prevent default form submission

                $.ajax({
                    url: "submit_ticket.php",
                    type: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response); // Show response message
                        $("#supportTicketForm")[0].reset(); // Reset form
                        $("#supportTicketModal").modal("hide"); // Hide modal
                    }
                });
            });
        });
    </script>


     <!-- /////////////////support ticket end //////////////////-->

    <!-- Show main content if user is activated -->
    <?php include("includes/hero.php"); ?>
    <?php include("includes/quick-menu.php"); ?>
    <?php include("includes/gallery.php"); ?>
    <?php include("includes/blog.php"); ?>
    <?php //include("includes/membership.php"); ?>
    <?php if ($taskAllowed): ?>
        <div class="d-flex justify-content-center">
          <div class="alert alert-success text-center">
            ✅ Tasks are available now (Monday to Friday, 11 AM - 5 PM).
          </div>
        </div>

        <?php include("includes/hometask.php"); ?>
    <?php else: ?>
        <div class="d-flex justify-content-center">
          <div class="alert alert-danger text-center">
           ⏳ Tasks are available only Monday to Friday, 11 AM - 5 PM
          </div>
        </div>
    <?php endif; ?>
    <?php include("includes/footer-nav.php"); ?>
    <?php include("includes/footer.php"); ?>

<?php elseif ($user_status == '0'): ?>
    <?php include("includes/hero.php"); ?>
    <?php include("includes/quick-menu.php"); ?>
    <?php include("includes/gallery.php"); ?>
    <?php include("includes/blog.php"); ?>
    <?php //include("includes/membership.php"); ?>
    <!-- Show message if user is not activated -->
    <div class="alert alert-warning mt-5">
        <h4>Your account is not activated yet!</h4>
        <p>Please wait for admin approval.</p>
    </div>
    <?php include("includes/footer-nav.php"); ?>
    <?php include("includes/footer.php"); ?>
<?php else: ?>
    <?php include("includes/hero.php"); ?>
    <!-- Banned Message -->
    <div class="alert alert-danger mt-5">
        <h4>Your account has been banned!</h4>
        <p>You have violated our policies. If you think this is a mistake, contact support.</p>
    </div>
<?php endif; ?>

