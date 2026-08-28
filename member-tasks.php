<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| MEMBER PROTECTION|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: dashboard.php");
    exit;
}


$member_id = (int) $_SESSION["user_id"];
$member_name = $_SESSION["full_name"] ?? "Team Member";


/*|--------------------------------------------------------------------------| GET MEMBER'S TASKS|--------------------------------------------------------------------------|*/

$tasks = [];

$query = "
    SELECT
        t.id,
        t.title,
        t.description,
        t.status,
        t.priority,
        t.due_date,
        t.created_at,
        p.name AS project_name,
        p.id AS project_id
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    WHERE t.assigned_to = ?
    ORDER BY
        CASE WHEN t.status != 'completed' THEN 0 ELSE 1 END,
        t.due_date ASC,
        t.created_at DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tasks[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*|--------------------------------------------------------------------------| COUNTS|--------------------------------------------------------------------------|*/

$total_tasks = count($tasks);
$pending_tasks = 0;
$in_progress_tasks = 0;
$completed_tasks = 0;

foreach ($tasks as $task) {
    if ($task["status"] === "completed") {
        $completed_tasks++;
    } elseif ($task["status"] === "in_progress") {
        $in_progress_tasks++;
    } else {
        $pending_tasks++;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .task-stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 22px; }
        .task-mini-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
        .task-mini-card span { display: block; color: #6b7280; font-size: 13px; margin-bottom: 8px; }
        .task-mini-card strong { font-size: 25px; color: #111827; }
        .task-title-cell strong { display: block; color: #111827; }
        .task-title-cell span { display: block; margin-top: 4px; color: #9ca3af; font-size: 12px; }
        .task-project { font-size: 13px; color: #374151; font-weight: 500; }
        .task-due { font-size: 13px; color: #4b5563; }
        .task-due.overdue { color: #dc2626; font-weight: 600; }
        @media (max-width: 900px) { .task-stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .task-stat-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="member-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="member-projects.php" class="nav-item"><span class="nav-icon">▣</span> My Projects</a>
            <a href="member-tasks.php" class="nav-item active"><span class="nav-icon">✓</span> My Tasks</a>
            <p class="nav-title">COLLABORATION</p>
            <a href="team.php" class="nav-item"><span class="nav-icon">♙</span> Team</a>
            <a href="notifications.php" class="nav-item"><span class="nav-icon">♧</span> Notifications</a>
            <p class="nav-title">SYSTEM</p>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-item"><span>↪</span> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
                <div class="search-box"><span>⌕</span><input type="text" id="taskSearch" placeholder="Search tasks..."></div>
            </div>
            <div class="topbar-right">
                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($member_name, 0, 2))) ?></div>
                    <div class="profile-info"><strong><?= htmlspecialchars($member_name) ?></strong><span>Team Member</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">TASKS</span>
                    <h1>My Tasks</h1>
                    <p>Tasks assigned to you across your projects.</p>
                </div>
                <div class="page-actions">
                    <span class="project-count"><?= $total_tasks ?> Task<?= $total_tasks != 1 ? "s" : "" ?></span>
                </div>
            </div>

            <!-- STATS -->
            <div class="task-stat-grid">
                <div class="task-mini-card"><span>Total Tasks</span><strong><?= $total_tasks ?></strong></div>
                <div class="task-mini-card"><span>Pending</span><strong><?= $pending_tasks ?></strong></div>
                <div class="task-mini-card"><span>In Progress</span><strong><?= $in_progress_tasks ?></strong></div>
                <div class="task-mini-card"><span>Completed</span><strong><?= $completed_tasks ?></strong></div>
            </div>

            <!-- TASK TABLE -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div><h2>My Tasks</h2><p>All tasks assigned to you</p></div>
                </div>

                <?php if (!empty($tasks)): ?>
                    <div class="table-container">
                        <table class="projects-table" id="tasksTable">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Project</th>
                                    <th>Due Date</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <?php
                                    $is_overdue = false;
                                    if (!empty($task["due_date"]) && $task["status"] !== "completed" && $task["due_date"] < date("Y-m-d")) {
                                        $is_overdue = true;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="task-title-cell">
                                                <strong><?= htmlspecialchars($task["title"]) ?></strong>
                                                <?php if (!empty($task["description"])): ?>
                                                    <span><?= htmlspecialchars(mb_strimwidth($task["description"], 0, 60, "...")) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><span class="task-project"><?= htmlspecialchars($task["project_name"]) ?></span></td>
                                        <td>
                                            <?php if (!empty($task["due_date"])): ?>
                                                <span class="task-due <?= $is_overdue ? "overdue" : "" ?>">
                                                    <?= htmlspecialchars($task["due_date"]) ?>
                                                    <?php if ($is_overdue): ?><br><small>Overdue</small><?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="priority-badge priority-<?= htmlspecialchars($task["priority"]) ?>"><?= ucfirst(htmlspecialchars($task["priority"])) ?></span></td>
                                        <td><span class="status-badge status-<?= htmlspecialchars($task["status"]) ?>"><?= ucfirst(str_replace("_", " ", htmlspecialchars($task["status"]))) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">✓</div>
                        <h3>No Tasks Yet</h3>
                        <p>No tasks have been assigned to you yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>

</div>

<script>
const searchInput = document.getElementById("taskSearch");
if (searchInput) {
    searchInput.addEventListener("input", function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll("#tasksTable tbody tr").forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(val) ? "" : "none";
        });
    });
}
</script>

</body>
</html>
