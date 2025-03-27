<?php 
include("includes/config.php");
//session_start(); // Ensure session is started

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

// Ensure user is logged in
if (!$user_mobile) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit;
}

// Fetch user's membership amount
$query = "SELECT amount FROM myapp_payment WHERE user_mobile = ? AND status = '1'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();
$user_membership = $result->fetch_assoc()['amount'] ?? 0; // Default to 0 if no membership

//print_r("SELECT amount FROM myapp_payment WHERE user_mobile = $user_mobile AND status = 1");die;
// Convert membership to integer
$user_membership = intval($user_membership);


//fetching tasks and videos from api
$api_url = "http://127.0.0.1:8000/api/tasks/";
$response = @file_get_contents($api_url);

if ($response === false) {
    echo "<p>Failed to fetch tasks. Check if Django is running.</p>";
    exit();
}

$tasks = json_decode($response, true);

if (!is_array($tasks)) {
    echo "<p>Invalid API response.</p>";
    exit();
}

$membership_levels = [];

if (!empty($tasks)) {
    foreach ($tasks as $task) {
        if (isset($task['amount'], $task['no_of_videos'], $task['earning'])) {
            $membership_levels[$task['amount']] = [
                'videos' => $task['no_of_videos'],
                'earning' => $task['earning'],
            ];
        }
    }
} else {
    echo "<p>No Task found.</p>";
}


// Define membership levels and their corresponding earnings
/* $membership_levels = [
    1100   => ['videos' => 5,  'earning' => 35],
    2100   => ['videos' => 7,  'earning' => 70],
    3100   => ['videos' => 10, 'earning' => 110],
    5100   => ['videos' => 15, 'earning' => 175],
    11000  => ['videos' => 20, 'earning' => 385],
    21000  => ['videos' => 25, 'earning' => 750],
    49000  => ['videos' => 30, 'earning' => 1800],
    99000  => ['videos' => 35, 'earning' => 1800]
]; */

// Get allowed videos and earning per video
$allowed_videos = $membership_levels[$user_membership]['videos'] ?? 0;
$earning_per_video = $membership_levels[$user_membership]['earning'] ?? 0;
$completed_tasks = 0;
$total_earning = $completed_tasks * $earning_per_video;
?>

<div class="container py-4">
    <h2 class="text-center mb-4">Task</h2>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4 text-center">
                <div class="col-md-3">
                    <h5>Today's Remaining Tasks: <span class="text-warning" id="remaining-tasks"><?php echo $allowed_videos - $completed_tasks; ?></span></h5>
                </div>
                <div class="col-md-3">
                    <h5>Complete the Task Today: <span class="text-danger" id="completed-tasks"><?php echo $completed_tasks; ?></span></h5>
                </div>
                <div class="col-md-3">
                    <h5>Total Earnings: <span id="earnings">0</span> INR</h5>
                </div>
                <div class="col-md-3">
                    <h5>Taken Membership: <?php echo $user_membership; ?></h5>
                    <a href="membership" class="flash-button mx-2">Upgrade Membership</a>
                </div>
            </div>

            <div class="row mb-4" id="task-buttons">
                <div class="col text-center">
                    <?php if ($allowed_videos == 0): ?>
                        <p class="text-danger">You don't have access to any tasks. Please purchase a membership.</p>
                        <a href="membership" class="flash-button mx-2">Purchase membership</a>
                    <?php else: ?>
                        <button class="btn flash-button mx-2 mb-2" onclick="showTasks(<?php echo $allowed_videos; ?>, <?php echo $earning_per_video; ?>)">
                            <i class="fa fa-video" aria-hidden="true"></i> Watch Videos to Complete Tasks
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div id="task-list" style="display: none; max-height: 500px; overflow-y: auto;">
                <button class="btn btn-success" id="back-button" onclick="goBack()" style="display: block;">&larr; Back</button>
                <br>
                <div class="row g-3" id="tasks-container"></div>
                <span id="membership-level">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    let videoCount = 0;
    let allowedVideos = 0;
    let totalEarnings = 0;
    let earningPerVideo = 0;
    let taskVideos = {}; // Store task-wise videos

    // Fetch Task Videos from Django API
    function fetchTaskVideos() {
        fetch("http://127.0.0.1:8000/api/tasks/") // Update this URL if needed
            .then(response => response.json())
            .then(tasks => {
                taskVideos = {}; // Reset the task videos object

                tasks.forEach(task => {
                    taskVideos[task.task_number] = {
                        videos: task.videos.map(video => video.video_url),
                        earnings: parseFloat(task.earning),
                        no_of_videos: task.no_of_videos
                    };
                });
            })
            .catch(error => console.error("Error fetching videos:", error));
    }

    // Call function to fetch videos on page load
    fetchTaskVideos();

    function updateEarnings() {
        document.getElementById("earnings").innerText = totalEarnings;
        document.getElementById("completed-tasks").innerText = videoCount;
        document.getElementById("remaining-tasks").innerText = allowedVideos - videoCount;
    }

    function showTasks(videoLimit, earnings) {
        allowedVideos = videoLimit;
        earningPerVideo = earnings;
        videoCount = 0;
        totalEarnings = 0;
        updateEarnings();

//         const videos = [
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4',
//             'https://ramagyagroup.com/video/rg-main.mp4'
//         ];

        const container = document.getElementById('tasks-container');
        container.innerHTML = '';

        for (let i = 0; i < videoLimit && i < videos.length; i++) {
            const videoDiv = document.createElement('div');
            videoDiv.className = 'col-12 col-md-6 col-lg-4';
            videoDiv.innerHTML = `
                <div class="video-wrapper">
                    <video id="video${i}" width="100%" controls>
                        <source src="${videos[i]}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <p class="bg-light white-text text-center flash-text p-10"><strong>Earning:</strong> INR. ${earningPerVideo} Per Video</p>
                </div>
            `;
            container.appendChild(videoDiv);

            document.getElementById(`video${i}`).addEventListener("play", function() {
                trackVideoPlay(i);
            });
        }

        document.getElementById('task-list').style.display = 'block';
        document.getElementById('task-buttons').style.display = 'none';
    }

    function trackVideoPlay(index) {
        const videoElement = document.getElementById(`video${index}`);

        if (videoElement.getAttribute("data-watched") === "true") {
            return;
        }

        if (videoCount >= allowedVideos) {
            alert("You have completed your allowed tasks for today.");
            return;
        }

        videoElement.setAttribute("data-watched", "true");
        videoCount++;
        totalEarnings += earningPerVideo;
        updateEarnings();
    }

    function goBack() {
        document.getElementById('task-list').style.display = 'none';
        document.getElementById('task-buttons').style.display = 'block';
    }
</script>