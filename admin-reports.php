<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

require_once "auth_check.php";

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


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";


/*|--------------------------------------------------------------------------| STATISTICS|--------------------------------------------------------------------------|*/

$total_users = 0;
$total_projects = 0;
$active_projects = 0;
$completed_projects = 0;
$total_tasks = 0;
$completed_tasks = 0;
$pending_tasks = 0;
$in_progress_tasks = 0;

$stats = [
    "users" => "SELECT COUNT(*) AS total FROM users",
    "projects" => "SELECT COUNT(*) AS total FROM projects",
    "active_projects" => "SELECT COUNT(*) AS total FROM projects WHERE status = 'in_progress'",
    "completed_projects" => "SELECT COUNT(*) AS total FROM projects WHERE status = 'completed'",
    "total_tasks" => "SELECT COUNT(*) AS total FROM tasks",
    "completed_tasks" => "SELECT COUNT(*) AS total FROM tasks WHERE status = 'completed'",
    "pending_tasks" => "SELECT COUNT(*) AS total FROM tasks WHERE status = 'to_do'",
    "in_progress_tasks" => "SELECT COUNT(*) AS total FROM tasks WHERE status = 'in_progress'"
];

foreach ($stats as $key => $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        ${$key} = (int) $row["total"];
    }
}


/*|--------------------------------------------------------------------------| PROJECT STATUS BREAKDOWN|--------------------------------------------------------------------------|*/

$project_statuses = [];

$result = mysqli_query($conn, "
    SELECT status, COUNT(*) AS total
    FROM projects
    GROUP BY status
    ORDER BY total DESC
");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $project_statuses[] = $row;
    }
}


/*|--------------------------------------------------------------------------| TASK STATUS BREAKDOWN|--------------------------------------------------------------------------|*/

$task_statuses = [];

$result = mysqli_query($conn, "
    SELECT status, COUNT(*) AS total
    FROM tasks
    GROUP BY status
    ORDER BY total DESC
");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $task_statuses[] = $row;
    }
}


/*|--------------------------------------------------------------------------| PRIORITY BREAKDOWN|--------------------------------------------------------------------------|*/

$priorities = [];

$result = mysqli_query($conn, "
    SELECT priority, COUNT(*) AS total
    FROM projects
    GROUP BY priority
    ORDER BY total DESC
");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $priorities[] = $row;
    }
}


/*|--------------------------------------------------------------------------| PERCENTAGES|--------------------------------------------------------------------------|*/

$project_completion = 0;
if ($total_projects > 0) {
    $project_completion = round(($completed_projects / $total_projects) * 100);
}

$task_completion = 0;
if ($total_tasks > 0) {
    $task_completion = round(($completed_tasks / $total_tasks) * 100);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
</script>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="admin-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="admin-users.php" class="nav-item"><span class="nav-icon">♙</span> Users</a>
            <a href="admin-projects.php" class="nav-item"><span class="nav-icon">▣</span> Projects</a>
            <p class="nav-title">MANAGEMENT</p>
            <a href="admin-reports.php" class="nav-item active"><span class="nav-icon">▥</span> Reports</a>
            <a href="admin-activity.php" class="nav-item"><span class="nav-icon">◷</span> Activity Logs</a>
            <p class="nav-title">ACCOUNT</p>
                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <a
                href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
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
                <div class="search-box"><span>⌕</span><input type="text" placeholder="Search..."></div>
            </div>
            <div class="topbar-right">
                                                <button
                    class="theme-toggle-btn"
                    onclick="toggleTheme()"
                    title="Toggle Theme"
                >
                    <span class="theme-icon-light">☀️</span>
                    <span class="theme-icon-dark">🌙</span>
                </button>
<button
                    class="notification-button"
                    type="button"
                    onclick="window.location.href='notifications.php'"
                    style="position:relative;"
                >
                    🔔
                    <span class="notification-dot" id="notifBadge" style="display:none;"></span>
                </button>

                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($admin_name, 0, 2))) ?></div>
                    <div class="profile-info"><strong><?= htmlspecialchars($admin_name) ?></strong><span>Administrator</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">REPORTS</span>
                    <h1>Reports</h1>
                    <p>Overview of system projects, tasks, and performance.</p>
                </div>
            </div>


            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">♙</div>
                    <div class="stat-info"><span>Total Users</span><strong><?= $total_users ?></strong></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">▣</div>
                    <div class="stat-info"><span>Total Projects</span><strong><?= $total_projects ?></strong></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✓</div>
                    <div class="stat-info"><span>Active Projects</span><strong><?= $active_projects ?></strong></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✔</div>
                    <div class="stat-info"><span>Completed Tasks</span><strong><?= $completed_tasks ?></strong></div>
                </div>
            </div>


            <!-- PROGRESS -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Project Completion</h2><p>Overall project progress</p></div></div>
                    <div class="progress-wrapper" style="margin-top:20px;">
                        <div class="progress-label" style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                            <span style="color:#6b7280;">Completion</span><strong><?= $project_completion ?>%</strong>
                        </div>
                        <div class="progress-bar" style="width:100%;height:10px;background:#e5e7eb;border-radius:20px;overflow:hidden;">
                            <div class="progress-fill" style="height:100%;background:#4f46e5;border-radius:20px;width:<?= $project_completion ?>%;"></div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Task Completion</h2><p>Overall task progress</p></div></div>
                    <div class="progress-wrapper" style="margin-top:20px;">
                        <div class="progress-label" style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:14px;">
                            <span style="color:#6b7280;">Completion</span><strong><?= $task_completion ?>%</strong>
                        </div>
                        <div class="progress-bar" style="width:100%;height:10px;background:#e5e7eb;border-radius:20px;overflow:hidden;">
                            <div class="progress-fill" style="height:100%;background:#4f46e5;border-radius:20px;width:<?= $task_completion ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- BREAKDOWNS -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Project Status</h2><p>Distribution by status</p></div></div>
                    <?php if (!empty($project_statuses)): ?>
                        <?php foreach ($project_statuses as $status): ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid #eee;">
                                <span class="status-badge status-<?= htmlspecialchars($status["status"]) ?>"><?= ucfirst(str_replace("_", " ", htmlspecialchars($status["status"]))) ?></span>
                                <strong><?= (int)$status["total"] ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:35px;text-align:center;color:#777;">No project data.</div>
                    <?php endif; ?>
                </div>

                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Task Status</h2><p>Distribution by status</p></div></div>
                    <?php if (!empty($task_statuses)): ?>
                        <?php foreach ($task_statuses as $status): ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid #eee;">
                                <span class="status-badge status-<?= htmlspecialchars($status["status"]) ?>"><?= ucfirst(str_replace("_", " ", htmlspecialchars($status["status"]))) ?></span>
                                <strong><?= (int)$status["total"] ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:35px;text-align:center;color:#777;">No task data.</div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- PRIORITIES -->
            <div class="dashboard-card">
                <div class="card-header"><div><h2>Project Priorities</h2><p>Projects by priority level</p></div></div>
                <?php if (!empty($priorities)): ?>
                    <?php foreach ($priorities as $p): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:13px 0;border-bottom:1px solid #eee;">
                            <span class="priority-badge priority-<?= htmlspecialchars($p["priority"]) ?>"><?= ucfirst(htmlspecialchars($p["priority"])) ?></span>
                            <strong><?= (int)$p["total"] ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding:35px;text-align:center;color:#777;">No priority data.</div>
                <?php endif; ?>
            </div>

        </section>
    </main>

</div><?php include "cookie_consent.php"; ?>
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
