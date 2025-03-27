<!-- Header with Logo and Dropdown -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <a class="navbar-brand" href="./">
      <!-- <img src="images/logo.png" alt="Logo" width="40" height="40" class="d-inline-block align-text-top"> -->
      The Earn Max
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <!-- <li class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle"
              href="#"
              id="navbarDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              Menu
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end"
              aria-labelledby="navbarDropdown"
            >
              <li><a class="dropdown-item" href="#">Profile</a></li>
              <li><a class="dropdown-item" href="#">Settings</a></li>
              <li><a class="dropdown-item" href="#">Logout</a></li>
            </ul>
          </li> -->
      </ul>
       <?php
          //session_start();
            include("includes/config.php"); // Ensure database connection

            $user_mobile = $_SESSION["user_mobile"]; // Assign session variable

            // Fetch user profile
            $query = "SELECT name, profile_image FROM myapp_profile WHERE user_mobile_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $user_mobile);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $name = $row['name'];
                $profile_image = "images/uploads/profile/" . $row['profile_image'] ?? "images/user-image-1.jpg"; // Default image
            } else {
                $name = "Guest"; // Set a default name
                $profile_image = "images/user-image-1.jpg"; // Default image
            }

            // Mask the mobile number (show only last 6 digits)
            $masked_mobile = "******" . substr($user_mobile, -4);

          if (!isset($user_mobile)) {
              echo '<a href="login.php" class="btn btn-outline-success btn-sm me-2">Log In</a>';
              echo '<a href="login.php" class="btn btn-success btn-sm me-2">Sign Up</a>';
          } else {
              echo '<p style="color:#fff"><img src="' . $profile_image . '" alt="' . $name . '" class="rounded-circle" width="25">&nbsp; Welcome / ' . $masked_mobile . ' / </p>';
              echo '<p><a href="logout.php">&nbsp; Logout</a></p>';
          }
        ?>
        
        
     <!--  <select class="form-select form-select-sm w-auto d-inline-block me-3">
        <option>Language</option>
        <option>English</option>
        <option>Hindi</option>
      </select>
      <a href="login" class="btn btn-outline-success btn-sm me-2">Log In</a>
      <a href="login" class="btn btn-success btn-sm me-2">Sign Up</a> -->

    </div>
   
  </div>
</nav>