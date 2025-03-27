<?php
session_start();
include("includes/config.php");

$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $mobile_number = $_POST['mobile_number'];

    // Default values for UPI and Bank details
    $upi_id = $_POST['upi_id'] ?? null;
    $account_number = $_POST['account_number'] ?? null;
    $bank_name = $_POST['bank_name'] ?? null;
    $ifsc_code = $_POST['ifsc_code'] ?? null;
    $branch_name = $_POST['branch_name'] ?? null;

    // Check if user already has a record
    $check_stmt = $conn->prepare("SELECT id, upi_id, account_number, bank_name, ifsc_code, branch_name FROM myapp_bankdetails WHERE user_mobile_id = ?");
    $check_stmt->bind_param("s", $user_mobile);
    $check_stmt->execute();
    $check_stmt->store_result();
    $has_existing_record = $check_stmt->num_rows > 0;
    $check_stmt->bind_result($id, $existing_upi, $existing_account, $existing_bank_name, $existing_ifsc_code, $existing_branch_name);
    $check_stmt->fetch();
    $check_stmt->close();

    // Preserve existing details if user is updating only one type
    if ($has_existing_record) {
        if (empty($upi_id)) $upi_id = $existing_upi;
        if (empty($account_number)) $account_number = $existing_account;
        if (empty($bank_name)) $bank_name = $existing_bank_name;
        if (empty($ifsc_code)) $ifsc_code = $existing_ifsc_code;
        if (empty($branch_name)) $branch_name = $existing_branch_name;

        // Update existing record
        $stmt = $conn->prepare("UPDATE myapp_bankdetails
                                SET name = ?, upi_id = ?, account_number = ?, bank_name = ?, ifsc_code = ?, branch_name = ?, mobile_number = ?, updated_at = NOW()
                                WHERE user_mobile_id = ?");
        $stmt->bind_param("ssssssss", $name, $upi_id, $account_number, $bank_name, $ifsc_code, $branch_name, $mobile_number, $user_mobile);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO myapp_bankdetails
                                (user_mobile_id, name, upi_id, account_number, bank_name, ifsc_code, branch_name, mobile_number, status, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, '0', NOW(), NOW())");
        $stmt->bind_param("ssssssss", $user_mobile, $name, $upi_id, $account_number, $bank_name, $ifsc_code, $branch_name, $mobile_number);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Bank details updated successfully!'); window.location.href='bank-details.php';</script>";
    } else {
        echo "<script>alert('Error updating bank details: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}
?>

<?php include("includes/head.php"); ?>
<?php include("includes/header.php"); ?>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-4" style="max-width: 500px; width: 100%;">
    <h2 class="text-center">Update Bank Details</h2>
    <form id="bank-form" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="mb-3">
            <label for="mobile_number" class="form-label">Mobile Number</label>
            <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="<?= htmlspecialchars($_SESSION['user_mobile']); ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label">Select Payment Type</label>
            <select class="form-control" id="payment_type" name="payment_type" onchange="togglePaymentFields()" required>
                <option value="">-- Select --</option>
                <option value="upi">UPI</option>
                <option value="bank">Bank Account</option>
            </select>
        </div>

        <div id="upi-details" class="payment-fields" style="display: none;">
            <div class="mb-3">
                <label for="upi_id" class="form-label">UPI ID</label>
                <input type="text" class="form-control" id="upi_id" name="upi_id">
            </div>
        </div>

        <div id="bank-details" class="payment-fields" style="display: none;">
            <div class="mb-3">
                <label for="account_number" class="form-label">Account Number</label>
                <input type="text" class="form-control" id="account_number" name="account_number">
            </div>

            <div class="mb-3">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name">
            </div>

            <div class="mb-3">
                <label for="ifsc_code" class="form-label">IFSC Code</label>
                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code">
            </div>

            <div class="mb-3">
                <label for="branch_name" class="form-label">Branch Name</label>
                <input type="text" class="form-control" id="branch_name" name="branch_name">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Update Details</button>
    </form>
</div>
</div>

<?php include("includes/footer-nav.php"); ?>
<?php include("includes/footer.php"); ?>

<script>
    function togglePaymentFields() {
        var paymentType = document.getElementById("payment_type").value;
        document.getElementById("upi-details").style.display = paymentType === "upi" ? "block" : "none";
        document.getElementById("bank-details").style.display = paymentType === "bank" ? "block" : "none";
    }
</script>
