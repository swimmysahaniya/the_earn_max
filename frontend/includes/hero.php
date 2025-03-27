<?php
//session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$mobile = $_SESSION["user_mobile"];
$referral_code = $_SESSION["referral_code"];
$site_url = "http://127.0.0.1/app/signup?ref=" . $referral_code; // Change to your actual site URL
?>
<!-- Header Banner -->
<div class="header-banner">
  <!-- Carousel -->
  <div class="container">
    <div class="row">
      <!-- Carousel (8 Columns) -->
      <div class="col-lg-8">
        <div id="appCarousel" class="carousel slide mt-3" data-bs-ride="carousel">
          <!-- Carousel Indicators -->
          <!-- <div class="carousel-indicators">
              <button type="button" data-bs-target="#appCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
              <button type="button" data-bs-target="#appCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            </div> -->

          <!-- Carousel Items -->
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="images/slide-1.png" class="d-block w-100" alt="Slide 1">
            </div>
            <div class="carousel-item">
              <img src="images/slide-1.png" class="d-block w-100" alt="Slide 2">
            </div>
          </div>

          <!-- Carousel Controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#appCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#appCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>

      <!-- Content (4 Columns) -->
      <div class="col-lg-4">
        <div class="mt-3">
          <div class="profile-card shadow">
            <img src="images/user-icon.png" alt="Profile" class="profile-img mb-2">
            <h5 class="mb-1">Registered</h5>
            <p class="text-muted small-text mb-3">70k<sup>+</sup> users</p>

            <button class="btn btn-orange flash-text btn-lg w-100 mb-0">
                Your Referral Code:
            <span id="referralCode">
              <?php echo $referral_code; ?>  
          </span>
          <button class="copy-btn" onclick="copyReferralCode()">Copy Referral Code</button>
           <script>
                function copyReferralCode() {
                    var referralCode = document.getElementById("referralCode").innerText;
                    navigator.clipboard.writeText(referralCode).then(function() {
                        alert("Referral code copied: " + referralCode);
                    }, function(err) {
                        console.error("Failed to copy: ", err);
                    });
                }
            </script>
              <!-- <span class="small-text">Mobile App</span> -->
            </button>
             
            <div class="d-flex justify-content-between mb-0">
              <button class="btn btn-light icon-btn">
                <i class="bi bi-collection"></i> Tutorial
              </button>
              <button class="btn btn-light icon-btn">
                <i class="bi bi-link"></i> Events
              </button>
              <button class="btn btn-light icon-btn" data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="bi bi-share"></i> Invite
              </button>
              <!-- <button class="invite-btn" data-bs-toggle="modal" data-bs-target="#shareModal">Invite</button> -->
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
                                  <a class="whatsapp" href="https://api.whatsapp.com/send?text=Join%20this%20platform%20and%20earn%20rewards!%20Use%20my%20referral%20link:%20<?php echo urlencode($site_url); ?>" target="_blank">Share on WhatsApp</a>
                                  <a class="facebook" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($site_url); ?>" target="_blank">Share on Facebook</a>
                                  <a class="instagram" href="https://www.instagram.com/?url=<?php echo urlencode($site_url); ?>" target="_blank">Share on Instagram</a>
                                  <a class="email" href="mailto:?subject=Join%20&%20Earn%20Rewards!&body=Join%20this%20platform%20and%20earn%20rewards!%20Use%20my%20referral%20link:%20<?php echo urlencode($site_url); ?>" target="_blank">Share via Email</a>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
             
            </div>

            <hr>
            <div class="d-flex justify-content-center small-text">
              <span class="me-2 text-success"><i class="bi bi-check-circle"></i> Membership</span>
              <a href="#" class="text-decoration-none text-muted me-3">What's This?</a>
              <!-- <span class="text-secondary"><i class="bi bi-person-circle"></i> Attribution Required</span>
            <a href="#" class="text-decoration-none text-muted ms-1">How?</a> -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- <div class="scrolling-text flash-text mt-2">
      <span class="white-text">
        Join together to get referral income and commission rebates. When your team develops to a certain scale, you can
        apply to become a city agent. The city agents can receive more.
      </span>
    </div> -->
  </div>



</div>