<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mobile = $_SESSION["user_mobile"];
$referral_code = $_SESSION["referral_code"];
$site_url = "http://127.0.0.1/app/signup?ref=" . $referral_code; // Change to your actual site URL
$share_message = urlencode("Join this platform and earn rewards! Use my referral link: " . $site_url);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .referral-box {
            border: 2px solid #4CAF50;
            padding: 10px;
            text-align: center;
            width: 300px;
            margin: auto;
            font-size: 18px;
            font-weight: bold;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .invite-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            border-radius: 5px;
        }
        .share-links a {
            display: block;
            margin: 5px 0;
            padding: 10px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .whatsapp { background: #25D366; }
        .facebook { background: #3b5998; }
        .instagram { background: #E4405F; }
        .email { background: #D44638; }
    </style>
</head>
<body>

    <div class="container text-center mt-5">
        <h2>Welcome, <?php echo $mobile; ?>!</h2>

        <div class="referral-box">
            Your Referral Code: <br>
            <span id="referralCode"><?php echo $referral_code; ?></span>
            <br>
            <button class="invite-btn" data-bs-toggle="modal" data-bs-target="#shareModal">Invite</button>
        </div>
    </div>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Share Your Referral Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Invite friends and earn rewards!</p>
                    <div class="share-links">
                        <a class="whatsapp" href="https://api.whatsapp.com/send?text=<?php echo $share_message; ?>" target="_blank">Share on WhatsApp</a>
                        <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($site_url); ?>&quote=<?php echo $share_message; ?>" target="_blank">Share on Facebook</a>
                        <a class="instagram" href="https://www.instagram.com/?url=<?php echo urlencode($site_url); ?>" target="_blank">Share on Instagram</a>
                        <a class="email" href="mailto:?subject=Join%20&%20Earn%20Rewards!&body=<?php echo $share_message; ?>" target="_blank">Share via Email</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
