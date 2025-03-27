<?php 
include("includes/config.php");
session_start(); // Ensure session is started

// Fetch user mobile number from session
$user_mobile = $_SESSION['user_mobile'] ?? '';

// Ensure user is logged in
if (!$user_mobile) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit;
}

// Fetch user's membership amount
$query = "SELECT amount FROM payments WHERE user_mobile = ? AND status = '1'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_mobile);
$stmt->execute();
$result = $stmt->get_result();
$user_membership = $result->fetch_assoc()['amount'] ?? 0; // Default to 0 if no membership

// Debugging: Check retrieved amount
// var_dump($user_membership);

// Convert membership to string for comparison (if necessary)
$user_membership = "INR " . intval($user_membership); 

// Define membership levels and allowed tasks
$membership_levels = [
    'INR 1100'  => ['Task 1'],
    'INR 2100'  => ['Task 2'],
    'INR 5100'  => ['Task 3'],
    'INR 11000' => ['Task 4'],
    'INR 21000' => ['Task 5'],
    'INR 51000' => ['Task 6']
    // Define membership levels and allowed task1,task2..........
    // 'INR 1100'  => ['Task 1'],
    // 'INR 2100'  => ['Task 1', 'Task 2'],
    // 'INR 5100'  => ['Task 1', 'Task 2', 'Task 3'],
    // 'INR 11000' => ['Task 1', 'Task 2', 'Task 3', 'Task 4'],
    // 'INR 21000' => ['Task 1', 'Task 2', 'Task 3', 'Task 4', 'Task 5'],
    // 'INR 51000' => ['Task 1', 'Task 2', 'Task 3', 'Task 4', 'Task 5', 'Task 6']
];

// Get allowed tasks for the user's membership
$allowed_tasks = $membership_levels[$user_membership] ?? [];

?>

<div class="container py-4">
    <h2 class="text-center mb-4">Task</h2>
    <div class="row mb-4" id="task-buttons">
        <div class="col text-center">
            <?php if (empty($allowed_tasks)): ?>
                <p class="text-danger">You don't have access to any tasks. Please purchase a membership.</p>
                <a href="membership" class="flash-button mx-2">Purchase membership</a>
            <?php else: ?>
                <?php foreach ($allowed_tasks as $task): ?>
                    <button class="btn btn-success mx-2 mb-2" onclick="showTasks('<?php echo $task; ?>')">
                        <i class="fa fa-video" aria-hidden="true"></i> <?php echo $task; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="task-list" style="display: none;">
        <div class="row mb-4">
            <div class="col text-start">
                <button class="btn btn-success" id="back-button" onclick="goBack()">&larr; Back</button>
            </div>
        </div>
        <div class="row g-3" id="tasks-container">
            <!-- Tasks will be dynamically loaded here -->
        </div>
    </div>
</div>

<script>
    const tasksData = {
        'Task 1': [{ membership: 'INR 1100', earning: 'INR 10 per task', videos: '5 Videos', videoUrls: ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY'] }],
        'Task 2': [{ membership: 'INR 2100', earning: 'INR 25 per task', videos: '7 Videos', videoUrls: ['https://www.youtube.com/embed/3JZ_D3ELwOQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY'] }],
        'Task 3': [{ membership: 'INR 5100', earning: 'INR 75 per task', videos: '10 Videos', videoUrls: ['https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY','https://www.youtube.com/embed/tgbNymZ7vqY'] }],
        'Task 4': [{ membership: 'INR 11000', earning: 'INR 225 per task', videos: '15 Videos', videoUrls: ['https://www.youtube.com/embed/V-_O7nl0Ii0'] }],
        'Task 5': [{ membership: 'INR 21000', earning: 'INR 675 per task', videos: '25 Videos', videoUrls: ['https://www.youtube.com/embed/Zi_XLOBDo_Y'] }],
        'Task 6': [{ membership: 'INR 51000', earning: 'INR 2025 per task', videos: '35 Videos', videoUrls: ['https://www.youtube.com/embed/hTWKbfoikeg'] }]
    };

    function showTasks(taskName) {
        const container = document.getElementById('tasks-container');
        container.innerHTML = ''; 

        if (tasksData[taskName]) {
            tasksData[taskName].forEach(task => {
                const taskCard = document.createElement('div');
                taskCard.className = 'col-12';
                taskCard.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col text-center">
                                    <h5>${taskName}</h5>
                                    <h5>Today's Remaining Tasks: <span class="text-warning">${task.videos}</span></h5>
                                    <h6>Complete the task today: <span class="text-danger">0</span></h6>
                                    <br>
                                    <p><strong>Taken Membership:</strong> ${task.membership}
                                    <a href="membership" class="flash-button mx-2">Upgrade membership</a></p>
                                </div>
                            </div>
                            <div class="row g-3">
                                ${task.videoUrls.map(video => `
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <iframe src="${video}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                        <p class="bg-light white-text text-center flash-text p-10"><strong>Earning:</strong> ${task.earning}</p>     
                                    </div>
                                `).join('')}
                            </div>
                        </div>  
                    </div>
                `;
                container.appendChild(taskCard);
            });
        }

        document.getElementById('task-list').style.display = 'block';
        document.getElementById('task-buttons').style.display = 'none';
        document.getElementById('back-button').style.display = 'inline-block';
    }

    function goBack() {
        document.getElementById('task-list').style.display = 'none';
        document.getElementById('task-buttons').style.display = 'block';
        document.getElementById('back-button').style.display = 'none';
    }
</script>
