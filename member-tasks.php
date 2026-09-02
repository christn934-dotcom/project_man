<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| MEMBER PROTECTION|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "avatar_helper.php";;

/* Update last_seen_at for notification badge tracking */
$__ls_uid = $_SESSION["user_id"] ?? 0;
if ($__ls_uid > 0) {
    $___ls = mysqli_prepare($conn, "UPDATE users SET last_seen_at = NOW() WHERE id = ?");
    if ($___ls) {
        mysqli_stmt_bind_param($___ls, "i", $__ls_uid);
        mysqli_stmt_execute($___ls);
        mysqli_stmt_close($___ls);
    }
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
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>

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
            <a href="member-settings.php" class="nav-item"><span class="nav-icon">⚙</span> Settings</a>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span>
                <span>Dark Mode</span>
                <span class="toggle-track"></span>
            </button>
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
                                                <button
                    class="theme-toggle-btn"
                    onclick="toggleTheme()"
                    title="Toggle Theme"
                >
                    <span class="theme-icon-light"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></span>
                    <span class="theme-icon-dark"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
                </button>
<button
                    class="notification-button"
                    type="button"
                    onclick="window.location.href='notifications.php'"
                    style="position:relative;"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="notification-dot" id="notifBadge" style="display:none;"></span>
                </button>

                <div class="admin-profile">
                    <?= render_avatar($_SESSION["profile_image"] ?? null, $member_name, (int)($_SESSION["user_id"])) ?>
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

            <?php if (isset($_GET["updated"]) && $_GET["updated"] == "1"): ?>
                <div class="success-alert">✓ Task status updated successfully.</div>
            <?php endif; ?>

            <?php if (isset($_GET["error"])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_GET["error"]) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET["info"])): ?>
                <div class="success-alert"><?= htmlspecialchars($_GET["info"]) ?></div>
            <?php endif; ?>

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
                                    <th>Action</th>
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
                                        <td>
                                            <?php if ($task["status"] === "to_do"): ?>
                                                <form method="POST" action="member-task-update.php" style="display:inline;">
                                                    <input type="hidden" name="task_id" value="<?= (int) $task["id"] ?>">
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="primary-button" style="padding:5px 12px; font-size:12px;">▶ Start</button>
                                                </form>
                                            <?php elseif ($task["status"] === "in_progress"): ?>
                                                <form method="POST" action="member-task-update.php" style="display:inline;">
                                                    <input type="hidden" name="task_id" value="<?= (int) $task["id"] ?>">
                                                    <input type="hidden" name="status" value="review">
                                                    <button type="submit" class="primary-button" style="padding:5px 12px; font-size:12px; background:#8b5cf6;">👁 Submit for Review</button>
                                                </form>
                                            <?php elseif ($task["status"] === "review"): ?>
                                                <span style="color:#ca8a04; font-size:12px; font-weight:600;">⏳ Awaiting Approval</span>
                                            <?php elseif ($task["status"] === "completed"): ?>
                                                <span style="color:#059669; font-size:13px;">✓ Approved & Done</span>
                                            <?php endif; ?>
                                        </td>
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
</script><?php include "cookie_consent.php"; ?>
<script src="dark_mode.php"></script>
<script src="assets/js/responsive.js"></script>

<script>
(function() {
    fetch('notification_count.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var badge = document.getElementById('notifBadge');
            if (badge && data.count > 0) {
                badge.style.display = 'block';
                badge.title = data.count + ' recent notifications';
                // Show count as text if > 0
                if (data.count > 99) {
                    badge.textContent = '99+';
                } else {
                    badge.textContent = data.count;
                }
                badge.style.width = 'auto';
                badge.style.height = 'auto';
                badge.style.padding = '1px 5px';
                badge.style.fontSize = '10px';
                badge.style.borderRadius = '10px';
                badge.style.fontWeight = '700';
            }
        })
        .catch(function() {});
})();
</script>
</body>

</html>
