<?php
session_start();
include("includes/config.php"); // Ensure database connection

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

$user_mobile = $_SESSION["user_mobile"];
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
        <h2 class="text-center mb-4">Support Ticket</h2>
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

<?php
    // Fetch refunds for display
    $tickets = [];
    $ticket_query = $conn->prepare("SELECT * FROM myapp_supportticket WHERE user_mobile_id = ?");
    $ticket_query->bind_param("s", $user_mobile);
    $ticket_query->execute();
    $result = $ticket_query->get_result();
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    $ticket_query->close();
?>

<div class="container mt-5">
    <h2 class="text-center">Your Ticket</h2>
    <?php if (!empty($tickets)) { ?>
        <table class="table">
            <tr>
                <th>Ticket ID</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php foreach ($tickets as $ticket) { ?>
                <tr>
                    <td><?php echo $ticket['ticket_id']; ?></td>
                    <td><?php echo $ticket['subject']; ?></td>
                    <td>
                        <?php
                            if ($ticket['status'] == 'open') {
                                echo 'Open';
                            } elseif ($ticket['status'] == 'in_progress') {
                                echo 'In Progress';
                            } else {
                                echo 'Closed';
                            }
                        ?>
                    </td>
                    <td><?php echo $ticket['created_at']; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="text-center">No ticket available.</p>
    <?php } ?>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>

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
                //$("#supportTicketModal").modal("hide"); // Hide modal
            }
        });
    });
});
</script>