<?php
session_start();

if (!isset($_SESSION["user_mobile"])) {
    header("Location: login.php");
    exit();
}
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Select a Membership Plan</h2>
    <div class="row">
        <?php
        // Define Membership Plans with Static UPI IDs
        $memberships = [
            ["task" => "Task 1", "amount" => 1100, "earning" => 10, "videos" => 5, "upis" => [
                "9598440100@upi", "9643798467@upi", "99569888921@upi"
            ]],
            ["task" => "Task 2", "amount" => 2100, "earning" => 25, "videos" => 7, "upis" => [
                "9598440100@upi", "upi5@bank", "upi6@bank"
            ]],
            ["task" => "Task 3", "amount" => 5100, "earning" => 75, "videos" => 10, "upis" => [
                "9598440100@upi", "upi8@bank", "upi9@bank"
            ]],
            ["task" => "Task 4", "amount" => 11000, "earning" => 15, "videos" => 10, "upis" => [
                "9598440100@upi", "upi11@bank", "upi12@bank"
            ]],
            ["task" => "Task 5", "amount" => 21000, "earning" => 25, "videos" => 10, "upis" => [
                "9598440100@upi", "upi14@bank", "upi15@bank"
            ]],
            ["task" => "Task 6", "amount" => 51000, "earning" => 35, "videos" => 10, "upis" => [
                "9598440100@upi", "upi17@bank", "upi18@bank"
            ]]
        ];

        foreach ($memberships as $index => $plan) {
        ?>
            <div class="col-md-4 mb-4">
                <div class="card text-center">
                    <div class="card-header btn btn-orange flash-text btn-lg w-100 mb-3 text-white">
                        <?= $plan["task"]; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Membership: INR <?= $plan["amount"]; ?></h5>
                        <p>Earning: INR <?= $plan["earning"]; ?> per task</p>
                        <p>Videos to Watch: <?= $plan["videos"]; ?></p>

                        <!-- UPI Payment Button -->
                        <button class="btn btn-success openModal" 
                                data-task="<?= $plan["task"]; ?>" 
                                data-amount="<?= $plan["amount"]; ?>"
                                data-upis='<?= json_encode($plan["upis"]); ?>'>
                            Pay via UPI
                        </button>

                        <!-- Upload Screenshot -->
                        <form action="upload" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="amount" value="<?= $plan['amount']; ?>">
                            <input type="hidden" id="upi_id" name="upi_id" value=""> <!-- Ensure this is updated dynamically -->
                            <br>
                            <input type="file" name="payment_screenshot" required class="form-control">
                            <button type="submit" class="btn btn-warning mt-2">Upload Screenshot</button>
                        </form>

                        <!-- <form action="upload" method="POST" enctype="multipart/form-data" class="mt-3">
                            <input type="hidden" name="amount" value="<?= $plan["amount"]; ?>">
                            <input type="file" name="payment_screenshot" required class="form-control">
                            <button type="submit" class="btn btn-warning mt-2">Upload Screenshot</button>
                        </form> -->
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
                <!-- <button type="button" class="close" data-dismiss="modal">&times;</button> -->
            </div>
            <div class="modal-body text-center">
                <p>Choose a UPI ID for payment:</p>
                <div id="upiOptions"></div>
                 <center><img id="qrImage" class="mt-3" src="" alt="UPI QR Code" style="display:none; width:200px; height:200px;"></center>
            </div>
            <!-- <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div> -->
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
                btn.textContent = `UPI ${index + 1}: ${upi}`;
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
