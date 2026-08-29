<?php

session_start();

require_once "config/database.php";

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


if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function format_date($date)
{
    if (empty($date)) {
        return "—";
    }

    $time = strtotime($date);

    if ($time === false) {
        return "—";
    }

    return date("M d, Y", $time);
}


/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

$total_users = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM users
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_users = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| PROJECT MANAGERS
|--------------------------------------------------------------------------
*/

$total_managers = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'project_manager'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_managers = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| TEAM MEMBERS
|--------------------------------------------------------------------------
*/

$total_members = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'member'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_members = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| TOTAL PROJECTS
|--------------------------------------------------------------------------
*/

$total_projects = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_projects = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| ACTIVE PROJECTS
|--------------------------------------------------------------------------
*/

$active_projects = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE status = 'in_progress'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $active_projects = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| COMPLETED PROJECTS
|--------------------------------------------------------------------------
*/

$completed_projects = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE status = 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $completed_projects = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| PENDING TASKS
|--------------------------------------------------------------------------
*/

$pending_tasks = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE status != 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $pending_tasks = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| COMPLETED TASKS
|--------------------------------------------------------------------------
*/

$completed_tasks = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE status = 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $completed_tasks = (int)$row["total"];
}


/*
|--------------------------------------------------------------------------
| TOTAL TASKS
|--------------------------------------------------------------------------
*/

$total_tasks = $pending_tasks + $completed_tasks;


/*
|--------------------------------------------------------------------------
| RECENT PROJECTS
|--------------------------------------------------------------------------
*/

$recent_projects = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.status,
        p.priority,
        p.start_date,
        p.end_date,
        u.full_name AS manager_name
    FROM projects p
    INNER JOIN users u
        ON p.manager_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $recent_projects[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| UPCOMING DEADLINES
|--------------------------------------------------------------------------
*/

$upcoming_deadlines = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.end_date,
        p.priority,
        u.full_name AS manager_name
    FROM projects p
    INNER JOIN users u
        ON p.manager_id = u.id
    WHERE p.end_date IS NOT NULL
      AND p.end_date >= CURDATE()
      AND p.status != 'completed'
    ORDER BY p.end_date ASC
    LIMIT 5
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $upcoming_deadlines[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| RECENT ACTIVITY
|--------------------------------------------------------------------------
*/

$activities = [];

$query = "
    SELECT
        a.action,
        a.description,
        a.created_at,
        u.full_name
    FROM activity_logs a
    INNER JOIN users u
        ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 6
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $activities[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| PROJECT COMPLETION
|--------------------------------------------------------------------------
*/

$project_completion = 0;

if ($total_projects > 0) {

    $project_completion = round(
        ($completed_projects / $total_projects) * 100
    );
}


/*
|--------------------------------------------------------------------------
| TASK COMPLETION
|--------------------------------------------------------------------------
*/

$task_completion = 0;

if ($total_tasks > 0) {

    $task_completion = round(
        ($completed_tasks / $total_tasks) * 100
    );
}


/*
|--------------------------------------------------------------------------
| USER PERCENTAGES
|--------------------------------------------------------------------------
*/

$manager_percentage = 0;
$member_percentage = 0;

if ($total_users > 0) {

    $manager_percentage = round(
        ($total_managers / $total_users) * 100
    );

    $member_percentage = round(
        ($total_members / $total_users) * 100
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | PMS</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .progress-wrapper {
            margin-top: 20px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .progress-label span {
            color: #6b7280;
        }

        .progress-label strong {
            color: #111827;
        }

        .progress-bar {
            width: 100%;
            height: 9px;
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 20px;
            background: #111827;
        }

        .overview-number {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }

        .dashboard-stat-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .dashboard-stat-link:hover .stat-card {
            transform: translateY(-2px);
        }

        .stat-card {
            transition: transform 0.2s ease;
        }

        .deadline-manager {
            display: block;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 4px;
        }

        .activity-action {
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background: #f8fafc;
        }

    </style>

</head>


<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
</script>


<div class="admin-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">


        <div class="sidebar-logo">

            <div class="logo-icon">
                P
            </div>

            <div>

                <h2>PMS</h2>

                <span>
                    Project Management
                </span>

            </div>

        </div>


        <nav class="sidebar-nav">


            <p class="nav-title">
                MAIN
            </p>


            <a
                href="admin-dashboard.php"
                class="nav-item active"
            >

                <span class="nav-icon">▦</span>

                Dashboard

            </a>


            <a
                href="projects.php"
                class="nav-item"
            >

                <span class="nav-icon">▣</span>

                Projects

            </a>


            <a
                href="tasks.php"
                class="nav-item"
            >

                <span class="nav-icon">✓</span>

                Tasks

            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="users.php"
                class="nav-item"
            >

                <span class="nav-icon">♙</span>

                Users

            </a>


            <a
                href="users.php?role=project_manager"
                class="nav-item"
            >

                <span class="nav-icon">♚</span>

                Project Managers

            </a>


            <a
                href="reports.php"
                class="nav-item"
            >

                <span class="nav-icon">▥</span>

                Reports

            </a>


                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <p class="nav-title">
                SYSTEM
            </p>


            <a
                href="settings.php"
                class="nav-item"
            >

                <span class="nav-icon">⚙</span>

                Settings

            </a>


            <a
                href="profile.php"
                class="nav-item"
            >

                <span class="nav-icon">◉</span>

                My Profile

            </a>


        </nav>


        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span>
                <span>Dark Mode</span>
                <span class="toggle-track"></span>
            </button>
            <a
                href="logout.php"
                class="logout-item"
            >

                <span>↪</span>

                Logout

            </a>

        </div>


    </aside>



    <!-- MAIN -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-left">


                <button
                    class="mobile-menu"
                    type="button"
                    id="mobileMenuButton"
                >
                    ☰
                </button>


                <div class="search-box">

                    <span>⌕</span>

                    <input
                        type="text"
                        id="dashboardSearch"
                        placeholder="Search recent projects..."
                    >

                </div>


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


                    <div class="profile-avatar">

                        <?= e(
                            strtoupper(
                                substr(
                                    $admin_name,
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


                    <div class="profile-info">

                        <strong>
                            <?= e($admin_name) ?>
                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>


                    <span class="profile-arrow">
                        ▾
                    </span>


                </div>


            </div>


        </header>



        <!-- CONTENT -->

        <section class="dashboard-content">


            <div class="page-header">


                <div>

                    <span class="page-label">
                        ADMINISTRATION
                    </span>


                    <h1>
                        Welcome back,
                        <?= e($admin_name) ?>!
                    </h1>


                    <p>
                        Here's an overview of your project management system.
                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="projects.php?action=create"
                        class="primary-button"
                    >
                        + New Project
                    </a>

                </div>


            </div>



            <!-- STATISTICS -->

            <div class="stats-grid">


                <a
                    href="users.php"
                    class="dashboard-stat-link"
                >

                    <div class="stat-card">

                        <div class="stat-icon">
                            ♙
                        </div>

                        <div class="stat-info">

                            <span>
                                Total Users
                            </span>

                            <strong>
                                <?= $total_users ?>
                            </strong>

                        </div>

                    </div>

                </a>


                <a
                    href="projects.php"
                    class="dashboard-stat-link"
                >

                    <div class="stat-card">

                        <div class="stat-icon">
                            ▣
                        </div>

                        <div class="stat-info">

                            <span>
                                Total Projects
                            </span>

                            <strong>
                                <?= $total_projects ?>
                            </strong>

                        </div>

                    </div>

                </a>


                <a
                    href="projects.php?status=in_progress"
                    class="dashboard-stat-link"
                >

                    <div class="stat-card">

                        <div class="stat-icon">
                            ↗
                        </div>

                        <div class="stat-info">

                            <span>
                                Active Projects
                            </span>

                            <strong>
                                <?= $active_projects ?>
                            </strong>

                        </div>

                    </div>

                </a>


                <a
                    href="tasks.php?status=pending"
                    class="dashboard-stat-link"
                >

                    <div class="stat-card">

                        <div class="stat-icon">
                            ✓
                        </div>

                        <div class="stat-info">

                            <span>
                                Pending Tasks
                            </span>

                            <strong>
                                <?= $pending_tasks ?>
                            </strong>

                        </div>

                    </div>

                </a>


            </div>



            <!-- USER OVERVIEW -->

            <div class="dashboard-grid">


                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Project Managers
                            </h2>

                            <p>
                                Managers currently registered
                            </p>

                        </div>

                        <span class="overview-number">
                            <?= $total_managers ?>
                        </span>

                    </div>


                    <div class="progress-wrapper">

                        <div class="progress-label">

                            <span>
                                Managers
                            </span>

                            <strong>
                                <?= $manager_percentage ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $manager_percentage ?>%;"
                            ></div>

                        </div>

                    </div>


                </div>



                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Team Members
                            </h2>

                            <p>
                                Members currently registered
                            </p>

                        </div>

                        <span class="overview-number">
                            <?= $total_members ?>
                        </span>

                    </div>


                    <div class="progress-wrapper">

                        <div class="progress-label">

                            <span>
                                Team Members
                            </span>

                            <strong>
                                <?= $member_percentage ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $member_percentage ?>%;"
                            ></div>

                        </div>

                    </div>


                </div>


            </div>



            <!-- PROJECT/TASK OVERVIEW -->

            <div class="dashboard-grid">


                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Project Overview
                            </h2>

                            <p>
                                Current project status
                            </p>

                        </div>

                    </div>


                    <div class="task-overview">


                        <div class="task-stat">

                            <span>
                                Active
                            </span>

                            <strong>
                                <?= $active_projects ?>
                            </strong>

                        </div>


                        <div class="task-stat">

                            <span>
                                Completed
                            </span>

                            <strong>
                                <?= $completed_projects ?>
                            </strong>

                        </div>


                        <div class="task-stat">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= $total_projects ?>
                            </strong>

                        </div>


                    </div>


                    <div class="progress-wrapper">

                        <div class="progress-label">

                            <span>
                                Project Completion
                            </span>

                            <strong>
                                <?= $project_completion ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $project_completion ?>%;"
                            ></div>

                        </div>

                    </div>


                </div>



                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Task Overview
                            </h2>

                            <p>
                                Current task progress
                            </p>

                        </div>

                    </div>


                    <div class="task-overview">


                        <div class="task-stat">

                            <span>
                                Pending
                            </span>

                            <strong>
                                <?= $pending_tasks ?>
                            </strong>

                        </div>


                        <div class="task-stat">

                            <span>
                                Completed
                            </span>

                            <strong>
                                <?= $completed_tasks ?>
                            </strong>

                        </div>


                        <div class="task-stat">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= $total_tasks ?>
                            </strong>

                        </div>


                    </div>


                    <div class="progress-wrapper">

                        <div class="progress-label">

                            <span>
                                Task Completion
                            </span>

                            <strong>
                                <?= $task_completion ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $task_completion ?>%;"
                            ></div>

                        </div>

                    </div>


                </div>


            </div>



            <!-- RECENT PROJECTS -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Recent Projects
                        </h2>

                        <p>
                            Latest projects created in the system
                        </p>

                    </div>


                    <a
                        href="projects.php"
                        class="text-button"
                    >
                        View All
                    </a>


                </div>



                <?php if (!empty($recent_projects)): ?>


                    <div class="table-container">


                        <table class="projects-table" id="recentProjectsTable">


                            <thead>

                                <tr>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Manager
                                    </th>

                                    <th>
                                        Start Date
                                    </th>

                                    <th>
                                        End Date
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($recent_projects as $project): ?>


                                    <tr
                                        class="clickable-row"
                                        onclick="window.location.href='project.php?id=<?= (int)$project["id"] ?>'"
                                    >


                                        <td>

                                            <div class="project-name">

                                                <div class="project-avatar">

                                                    <?= e(
                                                        strtoupper(
                                                            substr(
                                                                $project["name"],
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>
                                                        <?= e($project["name"]) ?>
                                                    </strong>

                                                </div>

                                            </div>

                                        </td>


                                        <td>
                                            <?= e($project["manager_name"]) ?>
                                        </td>


                                        <td>
                                            <?= e(format_date($project["start_date"])) ?>
                                        </td>


                                        <td>
                                            <?= e(format_date($project["end_date"])) ?>
                                        </td>


                                        <td>

                                            <span
                                                class="priority-badge priority-<?= e($project["priority"]) ?>"
                                            >
                                                <?= e(
                                                    ucfirst(
                                                        $project["priority"]
                                                    )
                                                ) ?>
                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="status-badge status-<?= e($project["status"]) ?>"
                                            >
                                                <?= e(
                                                    ucfirst(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $project["status"]
                                                        )
                                                    )
                                                ) ?>
                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            ▣
                        </div>

                        <h3>
                            No projects yet
                        </h3>

                        <p>
                            Create your first project to get started.
                        </p>

                        <a
                            href="projects.php?action=create"
                            class="primary-button"
                        >
                            + Create Project
                        </a>

                    </div>


                <?php endif; ?>


            </div>



            <!-- DEADLINES + ACTIVITY -->

            <div class="dashboard-grid">


                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Upcoming Deadlines
                            </h2>

                            <p>
                                Projects approaching their deadlines
                            </p>

                        </div>

                    </div>


                    <?php if (!empty($upcoming_deadlines)): ?>


                        <div class="deadline-list">


                            <?php foreach ($upcoming_deadlines as $deadline): ?>


                                <div
                                    class="deadline-item clickable-row"
                                    onclick="window.location.href='project.php?id=<?= (int)$deadline["id"] ?>'"
                                >


                                    <div>

                                        <strong>
                                            <?= e($deadline["name"]) ?>
                                        </strong>

                                        <span>
                                            Due:
                                            <?= e(format_date($deadline["end_date"])) ?>
                                        </span>

                                        <small class="deadline-manager">
                                            Manager:
                                            <?= e($deadline["manager_name"]) ?>
                                        </small>

                                    </div>


                                    <span
                                        class="priority-badge priority-<?= e($deadline["priority"]) ?>"
                                    >
                                        <?= e(
                                            ucfirst(
                                                $deadline["priority"]
                                            )
                                        ) ?>
                                    </span>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-state small">

                            <div class="empty-icon">
                                ✓
                            </div>

                            <p>
                                No upcoming deadlines.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>



                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Recent Activity
                            </h2>

                            <p>
                                Latest system activity
                            </p>

                        </div>

                    </div>


                    <?php if (!empty($activities)): ?>


                        <div class="activity-list">


                            <?php foreach ($activities as $activity): ?>


                                <div class="activity-item">


                                    <div class="activity-icon">
                                        •
                                    </div>


                                    <div>

                                        <strong>
                                            <?= e($activity["full_name"]) ?>
                                        </strong>


                                        <span class="activity-action">
                                            <?= e($activity["action"]) ?>
                                        </span>


                                        <p>
                                            <?= e($activity["description"]) ?>
                                        </p>


                                        <small>
                                            <?= e($activity["created_at"]) ?>
                                        </small>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-state small">

                            <div class="empty-icon">
                                •
                            </div>

                            <p>
                                No recent activity.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </div>


        </section>


    </main>


</div>


<script>

/*
|--------------------------------------------------------------------------
| DASHBOARD SEARCH
|--------------------------------------------------------------------------
*/

const dashboardSearch =
    document.getElementById("dashboardSearch");

if (dashboardSearch) {

    dashboardSearch.addEventListener(
        "input",
        function () {

            const searchValue =
                this.value.toLowerCase().trim();

            const rows =
                document.querySelectorAll(
                    "#recentProjectsTable tbody tr"
                );

            rows.forEach(function (row) {

                const text =
                    row.textContent.toLowerCase();

                row.style.display =
                    text.includes(searchValue)
                        ? ""
                        : "none";

            });

        }
    );

}

</script>


<?php include "cookie_consent.php"; ?>
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