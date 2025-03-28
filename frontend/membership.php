<?php
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}

include("includes/config.php");

// Get user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';


// Fetch Membership Plans from Database
$query = "SELECT task_number, title, amount, earning, no_of_videos FROM myapp_task ORDER BY task_number ASC, title ASC";
$result = $conn->query($query);

$memberships = [];
while ($row = $result->fetch_assoc()) {
    $memberships[] = $row;
}

// Sort tasks by `task_number` (numeric) and then by `title` (alphabetically)
usort($memberships, function ($a, $b) {
    // Compare task_number numerically
    if (intval($a['task_number']) == intval($b['task_number'])) {
        return strcmp($a['title'], $b['title']); // Sort alphabetically if task_number is the same
    }
    return intval($a['task_number']) - intval($b['task_number']); // Sort numerically
});

// Fetch UPI details from the database
$query = "SELECT * FROM myapp_upis where status = '1'";
$result = $conn->query($query);
$upi_list = [];
while ($row = $result->fetch_assoc()) {
    $upi_list[] = $row['upi_id'];
}

// Fetch all purchased membership amounts
$query = "SELECT DISTINCT amount FROM myapp_payment WHERE user_mobile_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();

// Store purchased memberships in an array
$purchased_memberships = [];
$max_purchased = 0;
while ($row = $result->fetch_assoc()) {
    $purchased_memberships[] = $row['amount'];
    if ($row['amount'] > $max_purchased) {
        $max_purchased = $row['amount'];
    }
}
?>


<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Select a Membership Plan</h2>
    <h4 style="color:red; text-align:center;">Note: After making the payment, please enter 12 digit transaction code. <br> The admin will verify the details, and once approved, your account will be activated for task participation.</h4>
    <div class="row mt-5">
        <?php
        foreach ($memberships as $plan) {
            // Skip memberships that are already purchased or lower than the highest purchased membership
            if (in_array($plan["amount"], $purchased_memberships) || $plan["amount"] < $max_purchased) {
                continue;
            }
        ?>
            <div class="col-md-4 mb-4">
                <div class="card text-center">
                    <div class="card-header btn btn-orange flash-text-1 btn-lg w-100 mb-3 text-white">
                        <?= $plan["title"]; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Membership: INR <?= $plan["amount"]; ?></h5>
                        <p>Earning: INR <?= $plan["earning"]; ?> per task</p>
                        <p>Videos to Watch: <?= $plan["no_of_videos"]; ?></p>

                        <!-- UPI Payment Button -->
                        <button class="btn btn-orange flash-text btn-lg openModal"
                                data-task="<?= $plan["title"]; ?>"
                                data-amount="<?= $plan["amount"]; ?>"
                                data-upis='<?= json_encode($upi_list); ?>'>
                            Pay via UPI
                        </button>

                        <!-- Upload Screenshot -->
                        <form action="upload.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="amount" value="<?= $plan['amount']; ?>">
                            <input type="hidden" id="upi_id" name="upi_id" value="">
                            <hr/>
                            <p style="color:red; text-align:center;">Note: Kindly enter your 12 digit transaction code using the button below for admin verification.</p>
                            <input type="text" placeholder="Enter 12 Digit Transaction Code" name="transaction_code" required class="form-control"><br/>
                            <!-- <input type="file" name="payment_screenshot" required class="form-control"><br/> -->
                            <button type="submit" class="btn btn-orange flash-text btn-sm mt-2">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>

<!-- Modal for Selecting UPI QR -->
<div id="upiModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select UPI for Payment</h5>
            </div>
            <div class="modal-body text-center">
                <p>Choose a UPI ID for payment:</p>
                <div id="upiOptions"></div>
                <img id="qrImage" class="mt-3" src="" alt="UPI QR Code" style="display:none; width:200px; height:200px;">
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll(".openModal").forEach(btn => {
        btn.addEventListener("click", function() {
            let upiList = JSON.parse(this.getAttribute("data-upis"));
            let amount = this.getAttribute("data-amount");
            let modalBody = document.getElementById("upiOptions");
            modalBody.innerHTML = ""; 

            upiList.forEach((upi, index) => {
                let btn = document.createElement("button");
                btn.className = "btn btn-orange flash-text btn-lg w-100 mb-3";
                btn.textContent = `UPI ${index + 1}`;
                btn.onclick = function() {
                    let paymentURL = `upi://pay?pa=${upi}&pn=The Earn Max&am=${amount}&cu=INR`;
                    document.getElementById("qrImage").src = `https://api.qrserver.com/v1/create-qr-code/?data=${encodeURIComponent(paymentURL)}&size=200x200`;
                    document.getElementById("qrImage").style.display = "block";
                };
                modalBody.appendChild(btn);
            });

            $("#upiModal").modal("show");
        });
    });
</script>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>
