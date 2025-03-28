<?php
include("includes/config.php");

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

if (!$user_mobile) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit;
}

// Fetch user's membership amount
$query = "SELECT amount FROM myapp_payment WHERE user_mobile_id = ? AND status = '1'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();
$user_membership = $result->fetch_assoc()['amount'] ?? 0;
$user_membership = intval($user_membership);

// Fetch completed tasks count and total earnings
$query = "SELECT
        SUM(ct.completed_tasks) AS completed_tasks,
        SUM(ct.total_earnings) AS total_earnings
    FROM myapp_completedtask AS ct
    INNER JOIN myapp_payment AS p ON ct.user_mobile = p.user_mobile_id
    INNER JOIN myapp_task AS t ON ct.task_id = t.id
    WHERE ct.user_mobile = ?
    AND p.status = '1'
    AND p.amount = t.amount";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$completed_tasks = $row['completed_tasks'] ?? 0;
$total_earnings = $row['total_earnings'] ?? 0;

//print_r($result);die;

// Fetch tasks and videos from Django API
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

// Organize membership levels
$membership_levels = [];
if (!empty($tasks)) {
    foreach ($tasks as $task) {
        if (isset($task['amount'], $task['no_of_videos'], $task['earning'], $task['task_number'], $task['videos'], $task['id'])) {
            $membership_levels[$task['amount']] = [
                'task_number' => $task['task_number'],
                'videos' => $task['videos'],
                'no_of_videos' => $task['no_of_videos'],
                'earning' => $task['earning'],
                'task_id' => $task['id'],
            ];
        }
    }
}

// Get user's task and videos based on membership level
$user_task = $membership_levels[$user_membership]['task_number'] ?? 0;
$total_videos = $membership_levels[$user_membership]['no_of_videos'] ?? 0;
$earning_per_video = $membership_levels[$user_membership]['earning'] ?? 0;
$task_videos = $membership_levels[$user_membership]['videos'] ?? [];
$task_id = $membership_levels[$user_membership]['task_id'] ?? 0;

// Calculate remaining tasks
$remaining_tasks = max(0, $total_videos - $completed_tasks);
?>

<div class="container py-4">
    <h2 class="text-center mb-4">Task</h2>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4 text-center">
                <div class="col-md-3">
                    <h5>Today's Remaining Tasks: <span class="text-warning" id="remaining-tasks"><?php echo $remaining_tasks; ?></span></h5>
                </div>
                <div class="col-md-3">
                    <h5>Completed Tasks: <span class="text-danger" id="completed-tasks"><?php echo $completed_tasks; ?></span></h5>
                </div>
                <div class="col-md-3">
                    <h5>Total Earnings: <span id="earnings"><?php echo $total_earnings; ?></span> INR</h5>
                </div>
                <div class="col-md-3">
                    <h5>Taken Membership: <?php echo $user_membership; ?></h5>
                    <a href="membership.php" class="flash-button mx-2">Upgrade Membership</a>
                </div>
            </div>

            <div class="row mb-4" id="task-buttons">
                <div class="col text-center">
                    <?php if ($completed_tasks == 0): ?>
                        <p class="text-primary">Welcome! Start your first task by watching the videos below.</p>
                        <button class="btn flash-button mx-2 mb-2" id="watch-videos-btn">
                            <i class="fa fa-video" aria-hidden="true"></i> Start Watching Videos
                        </button>
                    <?php elseif ($remaining_tasks == 0): ?>
                        <p class="text-danger">You have completed your tasks for today.</p>
                    <?php else: ?>
                        <button class="btn flash-button mx-2 mb-2" id="watch-videos-btn">
                            <i class="fa fa-video" aria-hidden="true"></i> Watch Videos to Complete Tasks
                        </button>
                    <?php endif; ?>
                </div>
            </div>


            <div id="task-list" style="display: none; max-height: 500px; overflow-y: auto;">
                <button class="btn btn-success" id="back-button" onclick="goBack()" style="display: block;">&larr; Back</button>
                <br>
                <div class="row g-3" id="tasks-container"></div>
            </div>
        </div>
    </div>
</div>

<script>
let videoCount = <?php echo $completed_tasks; ?>;
let allowedVideos = <?php echo $total_videos; ?>;
let totalEarnings = <?php echo $total_earnings; ?>;
let earningPerVideo = <?php echo $earning_per_video; ?>;
let remainingTasks = <?php echo $remaining_tasks; ?>;

const videoBasePath = "http://127.0.0.1:8000/media/videos/";
let taskId = <?php echo json_encode($task_id); ?>;
let taskVideos = <?php echo json_encode($task_videos); ?>;
let videoUrls = taskVideos.map(video => video.video_url.startsWith("http") ? video.video_url : videoBasePath + video.video_url.split('/').pop());

let watchingVideoIndex = 0;
let watchedVideos = new Set();

function updateEarnings() {
    document.getElementById("earnings").innerText = totalEarnings;
    document.getElementById("completed-tasks").innerText = videoCount;
    document.getElementById("remaining-tasks").innerText = allowedVideos - videoCount;
}

function showTasks() {
    const container = document.getElementById('tasks-container');
    container.innerHTML = '';

    videoUrls.forEach((videoUrl, i) => {
        const videoDiv = document.createElement('div');
        videoDiv.className = 'col-12 col-md-6 col-lg-4';

        videoDiv.innerHTML = `
            <div class="video-wrapper">
                <video id="video${i}" width="100%" style="height: 228px;" ${i !== 0 ? 'disabled' : ''} controls>
                    <source src="${videoUrl}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <p id="status${i}" class="bg-light white-text text-center flash-text p-10">
                    <strong>Earning:</strong> INR. ${earningPerVideo} Per Video
                </p>
            </div>
        `;

        container.appendChild(videoDiv);

        let videoElement = document.getElementById(`video${i}`);
        let statusElement = document.getElementById(`status${i}`);

        // Prevent fast-forwarding
        videoElement.addEventListener("timeupdate", function () {
            if (this.currentTime > this.lastTime + 2) {
                this.currentTime = this.lastTime;
            }
            this.lastTime = this.currentTime;
        });

        // Play event listener
        videoElement.addEventListener("play", function () {
            if (i !== watchingVideoIndex) {
                this.pause();
            } else {
                disableOtherVideos(i);
            }
        });

        // Track video completion
        videoElement.addEventListener("ended", function () {
            markVideoCompleted(i, videoElement, statusElement);
        });
    });

    document.getElementById('task-list').style.display = 'block';
    document.getElementById('task-buttons').style.display = 'none';
}

function disableOtherVideos(activeIndex) {
    videoUrls.forEach((_, i) => {
        let videoElement = document.getElementById(`video${i}`);
        if (i !== activeIndex) {
            videoElement.setAttribute("disabled", true);
        }
    });
}

function markVideoCompleted(index, videoElement, statusElement) {
    watchedVideos.add(index);
    videoElement.setAttribute("data-watched", "true");
    videoElement.style.border = "0px solid green";
    videoElement.controls = false;
    videoElement.style.pointerEvents = "none";
    statusElement.innerHTML = `<strong style="color:#fff;">✔ Video Completed</strong>`;

    videoCount++;
    totalEarnings += earningPerVideo;
    updateEarnings();

    if (videoCount < allowedVideos) {
        let nextVideo = document.getElementById(`video${index + 1}`);
        if (nextVideo) {
            nextVideo.removeAttribute("disabled");
            watchingVideoIndex = index + 1;
        }
    }

    saveTaskProgress();
}

function saveTaskProgress() {
    fetch("save_task.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `task_id=${encodeURIComponent(taskId)}&completed_tasks=1&total_earnings=${encodeURIComponent(earningPerVideo)}`,
    })
    .then(response => response.json())
    .then(data => console.log("Server Response:", data))
    .catch(error => console.error("Error:", error));
}

function goBack() {
    document.getElementById('task-list').style.display = 'none';
    document.getElementById('task-buttons').style.display = 'block';
}

// Ensure only one video can play at a time
document.getElementById("watch-videos-btn").addEventListener("click", showTasks);
</script>
