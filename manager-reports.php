<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}

$manager_id = (int) $_SESSION["user_id"];
$manager_name = $_SESSION["full_name"] ?? "Project Manager";


/*
|--------------------------------------------------------------------------
| PROJECT STATISTICS
|--------------------------------------------------------------------------
*/

$total_projects = 0;
$active_projects = 0;
$completed_projects = 0;
$pending_projects = 0;
$overdue_projects = 0;


/* Total Projects */

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_projects = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Active Projects */

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND status = 'in_progress'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $active_projects = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Completed Projects */

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND status = 'completed'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $completed_projects = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Pending Projects */

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND status IN ('planning', 'on_hold')
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $pending_projects = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Overdue Projects */

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND end_date IS NOT NULL
    AND end_date < CURDATE()
    AND status != 'completed'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $overdue_projects = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| TASK STATISTICS
|--------------------------------------------------------------------------
*/

$total_tasks = 0;
$completed_tasks = 0;
$pending_tasks = 0;
$in_progress_tasks = 0;
$review_tasks = 0;


/* Total Tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_tasks = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Completed Tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    AND t.status = 'completed'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $completed_tasks = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Pending Tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    AND t.status = 'to_do'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $pending_tasks = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* In Progress Tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    AND t.status = 'in_progress'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $in_progress_tasks = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/* Review Tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    AND t.status = 'review'
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $review_tasks = (int) $row["total"];
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| TASK COMPLETION PERCENTAGE
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
| PROJECT COMPLETION PERCENTAGE
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
| PROJECT STATUS BREAKDOWN
|--------------------------------------------------------------------------
*/

$project_statuses = [];

$query = "
    SELECT
        status,
        COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    GROUP BY status
    ORDER BY total DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $project_statuses[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| TASK STATUS BREAKDOWN
|--------------------------------------------------------------------------
*/

$task_statuses = [];

$query = "
    SELECT
        t.status,
        COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    GROUP BY t.status
    ORDER BY total DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $task_statuses[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| PRIORITY BREAKDOWN
|--------------------------------------------------------------------------
*/

$priorities = [];

$query = "
    SELECT
        priority,
        COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    GROUP BY priority
    ORDER BY total DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $priorities[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| PROJECT PERFORMANCE
|--------------------------------------------------------------------------
*/

$project_performance = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.status,
        p.priority,
        p.start_date,
        p.end_date,

        COUNT(t.id) AS total_tasks,

        SUM(
            CASE
                WHEN t.status = 'completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_tasks

    FROM projects p

    LEFT JOIN tasks t
        ON t.project_id = p.id

    WHERE p.manager_id = ?

    GROUP BY
        p.id,
        p.name,
        p.status,
        p.priority,
        p.start_date,
        p.end_date

    ORDER BY p.created_at DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $project_performance[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| UPCOMING / OVERDUE DEADLINES
|--------------------------------------------------------------------------
*/

$deadline_projects = [];

$query = "
    SELECT
        id,
        name,
        end_date,
        priority,
        status
    FROM projects
    WHERE manager_id = ?
    AND end_date IS NOT NULL
    AND status != 'completed'
    ORDER BY end_date ASC
    LIMIT 8
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $deadline_projects[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
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

    <title>
        Reports | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .report-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }

        .report-stat {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #eee;
        }

        .report-stat span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .report-stat strong {
            display: block;
            font-size: 28px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .report-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            border: 1px solid #eee;
        }

        .report-card h2 {
            margin: 0 0 6px;
        }

        .report-card > p {
            color: #777;
            margin-top: 0;
        }

        .report-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #eee;
        }

        .report-row:last-child {
            border-bottom: none;
        }

        .report-row-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-count {
            font-weight: 700;
        }

        .report-progress {
            margin-top: 20px;
        }

        .report-progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .report-progress-bar {
            height: 10px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
        }

        .report-progress-fill {
            height: 100%;
            background: #4f46e5;
            border-radius: 20px;
        }

        .report-alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #fff5f5;
            border: 1px solid #f1caca;
        }

        .report-alert strong {
            display: block;
            margin-bottom: 5px;
        }

        .report-alert span {
            color: #777;
            font-size: 13px;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .performance-table th,
        .performance-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .performance-table th {
            font-size: 13px;
            color: #777;
        }

        .performance-project-name {
            font-weight: 600;
        }

        .mini-progress {
            width: 100px;
            height: 7px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
        }

        .mini-progress-fill {
            height: 100%;
            background: #4f46e5;
        }

        .empty-report {
            padding: 35px;
            text-align: center;
            color: #777;
        }

        @media (max-width: 1100px) {

            .report-stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 800px) {

            .report-grid {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

        }

        @media (max-width: 550px) {

            .report-stat-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <div class="logo-icon">
                P
            </div>

            <div>

                <h2>
                    PMS
                </h2>

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
                href="manager-dashboard.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="manager-projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                My Projects

            </a>


            <a
                href="manager-tasks.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Tasks

            </a>


            <p class="nav-title">
                WORKSPACE
            </p>


            <a
                href="manager-team.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Team

            </a>


            <a
                href="manager-reports.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

            </a>


            <p class="nav-title">
                ACCOUNT
            </p>


                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <a
                href="manager-settings.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ⚙
                </span>

                Settings

            </a>

            <a
                href="profile.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ◉
                </span>

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

                <span>
                    ↪
                </span>

                Logout

            </a>

        </div>


    </aside>



    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-left">


                <button
                    class="mobile-menu"
                    type="button"
                >
                    ☰
                </button>


                <div class="search-box">

                    <span>
                        ⌕
                    </span>

                    <input
                        type="text"
                        placeholder="Search..."
                    >

                </div>


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


                    <?= render_avatar($_SESSION["profile_image"] ?? null, $manager_name, (int)($_SESSION["user_id"])) ?>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $manager_name
                            ) ?>

                        </strong>

                        <span>
                            Project Manager
                        </span>

                    </div>


                    <span class="profile-arrow">
                        ▾
                    </span>


                </div>


            </div>


        </header>



        <!-- =====================================================
             REPORT CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        REPORTS
                    </span>

                    <h1>
                        Project Reports
                    </h1>

                    <p>
                        Overview of your projects, tasks and performance.
                    </p>

                </div>


            </div>



            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="report-stat-grid">


                <div class="report-stat">

                    <span>
                        Total Projects
                    </span>

                    <strong>
                        <?= $total_projects ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Active Projects
                    </span>

                    <strong>
                        <?= $active_projects ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Completed Projects
                    </span>

                    <strong>
                        <?= $completed_projects ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Overdue Projects
                    </span>

                    <strong>
                        <?= $overdue_projects ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Total Tasks
                    </span>

                    <strong>
                        <?= $total_tasks ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Pending Tasks
                    </span>

                    <strong>
                        <?= $pending_tasks ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        In Progress Tasks
                    </span>

                    <strong>
                        <?= $in_progress_tasks ?>
                    </strong>

                </div>


                <div class="report-stat">

                    <span>
                        Completed Tasks
                    </span>

                    <strong>
                        <?= $completed_tasks ?>
                    </strong>

                </div>


            </div>



            <!-- =================================================
                 PROGRESS
            ================================================== -->

            <div class="report-grid">


                <div class="report-card">


                    <h2>
                        Project Completion
                    </h2>

                    <p>
                        Percentage of your projects marked completed.
                    </p>


                    <div class="report-progress">


                        <div class="report-progress-header">

                            <span>
                                Completion
                            </span>

                            <strong>
                                <?= $project_completion ?>%
                            </strong>

                        </div>


                        <div class="report-progress-bar">

                            <div
                                class="report-progress-fill"
                                style="width: <?= $project_completion ?>%;"
                            ></div>

                        </div>


                    </div>


                </div>



                <div class="report-card">


                    <h2>
                        Task Completion
                    </h2>

                    <p>
                        Percentage of tasks completed across your projects.
                    </p>


                    <div class="report-progress">


                        <div class="report-progress-header">

                            <span>
                                Completion
                            </span>

                            <strong>
                                <?= $task_completion ?>%
                            </strong>

                        </div>


                        <div class="report-progress-bar">

                            <div
                                class="report-progress-fill"
                                style="width: <?= $task_completion ?>%;"
                            ></div>

                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 BREAKDOWNS
            ================================================== -->

            <div class="report-grid">


                <!-- PROJECT STATUS -->

                <div class="report-card">


                    <h2>
                        Project Status
                    </h2>

                    <p>
                        Distribution of your projects by status.
                    </p>


                    <?php if (!empty($project_statuses)): ?>


                        <?php foreach (
                            $project_statuses
                            as $status
                        ): ?>


                            <div class="report-row">


                                <div class="report-row-left">

                                    <span
                                        class="status-badge status-<?= htmlspecialchars(
                                            $status["status"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $status["status"]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </div>


                                <span class="report-count">

                                    <?= (int) $status["total"] ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-report">
                            No project data available.
                        </div>


                    <?php endif; ?>


                </div>



                <!-- TASK STATUS -->

                <div class="report-card">


                    <h2>
                        Task Status
                    </h2>

                    <p>
                        Distribution of tasks across your projects.
                    </p>


                    <?php if (!empty($task_statuses)): ?>


                        <?php foreach (
                            $task_statuses
                            as $status
                        ): ?>


                            <div class="report-row">


                                <div class="report-row-left">

                                    <span
                                        class="status-badge status-<?= htmlspecialchars(
                                            $status["status"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $status["status"]
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </div>


                                <span class="report-count">

                                    <?= (int) $status["total"] ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-report">
                            No task data available.
                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- =================================================
                 PRIORITIES + DEADLINES
            ================================================== -->

            <div class="report-grid">


                <!-- PRIORITIES -->

                <div class="report-card">


                    <h2>
                        Project Priorities
                    </h2>

                    <p>
                        Number of projects by priority.
                    </p>


                    <?php if (!empty($priorities)): ?>


                        <?php foreach (
                            $priorities
                            as $priority
                        ): ?>


                            <div class="report-row">


                                <div class="report-row-left">

                                    <span
                                        class="priority-badge priority-<?= htmlspecialchars(
                                            $priority["priority"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                $priority["priority"]
                                            )
                                        ) ?>

                                    </span>

                                </div>


                                <span class="report-count">

                                    <?= (int) $priority["total"] ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-report">
                            No priority data available.
                        </div>


                    <?php endif; ?>


                </div>



                <!-- DEADLINES -->

                <div class="report-card">


                    <h2>
                        Upcoming & Overdue
                    </h2>

                    <p>
                        Projects that still have active deadlines.
                    </p>


                    <?php if (!empty($deadline_projects)): ?>


                        <?php foreach (
                            $deadline_projects
                            as $deadline
                        ): ?>


                            <?php

                            $is_overdue =
                                strtotime(
                                    $deadline["end_date"]
                                ) < strtotime(date("Y-m-d"));

                            ?>


                            <div class="report-alert">


                                <strong>

                                    <?= htmlspecialchars(
                                        $deadline["name"]
                                    ) ?>

                                </strong>


                                <span>

                                    <?php if ($is_overdue): ?>

                                        Overdue:
                                        <?= htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $deadline["end_date"]
                                                )
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        Due:
                                        <?= htmlspecialchars(
                                            date(
                                                "M d, Y",
                                                strtotime(
                                                    $deadline["end_date"]
                                                )
                                            )
                                        ) ?>

                                    <?php endif; ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-report">

                            No active project deadlines.

                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- =================================================
                 PROJECT PERFORMANCE
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Project Performance
                        </h2>

                        <p>
                            Task completion for each project.
                        </p>

                    </div>


                </div>


                <?php if (!empty($project_performance)): ?>


                    <div class="table-container">


                        <table class="performance-table">


                            <thead>

                                <tr>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Tasks
                                    </th>

                                    <th>
                                        Completed
                                    </th>

                                    <th>
                                        Progress
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $project_performance
                                    as $project
                                ): ?>


                                    <?php

                                    $project_total_tasks =
                                        (int) $project["total_tasks"];

                                    $project_completed_tasks =
                                        (int) $project["completed_tasks"];

                                    $project_progress = 0;

                                    if (
                                        $project_total_tasks > 0
                                    ) {

                                        $project_progress = round(
                                            (
                                                $project_completed_tasks
                                                /
                                                $project_total_tasks
                                            ) * 100
                                        );

                                    }

                                    ?>


                                    <tr>


                                        <td>

                                            <div class="performance-project-name">

                                                <?= htmlspecialchars(
                                                    $project["name"]
                                                ) ?>

                                            </div>

                                        </td>


                                        <td>

                                            <span
                                                class="status-badge status-<?= htmlspecialchars(
                                                    $project["status"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
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


                                        <td>

                                            <span
                                                class="priority-badge priority-<?= htmlspecialchars(
                                                    $project["priority"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        $project["priority"]
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= $project_total_tasks ?>

                                        </td>


                                        <td>

                                            <?= $project_completed_tasks ?>

                                        </td>


                                        <td>


                                            <div style="
                                                display:flex;
                                                align-items:center;
                                                gap:10px;
                                            ">


                                                <div class="mini-progress">

                                                    <div
                                                        class="mini-progress-fill"
                                                        style="
                                                            width: <?= $project_progress ?>%;
                                                        "
                                                    ></div>

                                                </div>


                                                <span>

                                                    <?= $project_progress ?>%

                                                </span>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-report">

                        No projects available for reporting.

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


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