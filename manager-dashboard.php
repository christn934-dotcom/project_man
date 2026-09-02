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
| TOTAL PROJECTS
|--------------------------------------------------------------------------
*/

$total_projects = 0;

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


/*
|--------------------------------------------------------------------------
| ACTIVE PROJECTS
|--------------------------------------------------------------------------
*/

$active_projects = 0;

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


/*
|--------------------------------------------------------------------------
| COMPLETED PROJECTS
|--------------------------------------------------------------------------
*/

$completed_projects = 0;

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


/*
|--------------------------------------------------------------------------
| PENDING TASKS
|--------------------------------------------------------------------------
*/

$pending_tasks = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    AND t.status != 'completed'
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


/*
|--------------------------------------------------------------------------
| COMPLETED TASKS
|--------------------------------------------------------------------------
*/

$completed_tasks = 0;

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


/*
|--------------------------------------------------------------------------
| RECENT PROJECTS
|--------------------------------------------------------------------------
*/

$recent_projects = [];

$query = "
    SELECT
        id,
        name,
        description,
        status,
        priority,
        start_date,
        end_date,
        created_at
    FROM projects
    WHERE manager_id = ?
    ORDER BY created_at DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $recent_projects[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| UPCOMING DEADLINES
|--------------------------------------------------------------------------
*/

$upcoming_deadlines = [];

$query = "
    SELECT
        id,
        name,
        end_date,
        priority,
        status,
        DATEDIFF(end_date, CURDATE()) AS days_remaining
    FROM projects
    WHERE manager_id = ?
    AND end_date IS NOT NULL
    AND status != 'completed'
    AND end_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND end_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    ORDER BY end_date ASC
    LIMIT 10
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $upcoming_deadlines[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| RECENT TASKS
|--------------------------------------------------------------------------
*/

$recent_tasks = [];

$query = "
    SELECT
        t.id,
        t.title,
        t.status,
        t.priority,
        t.due_date,
        p.name AS project_name
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $manager_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $recent_tasks[] = $row;
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
        Manager Dashboard | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

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
                class="nav-item active"
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
                class="nav-item"
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


        <!-- =================================================
             TOPBAR
        ================================================== -->

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



        <!-- =================================================
             DASHBOARD
        ================================================== -->

        <section class="dashboard-content">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        PROJECT MANAGER
                    </span>


                    <h1>

                        Welcome back,
                        <?= htmlspecialchars(
                            $manager_name
                        ) ?>!

                    </h1>


                    <p>

                        Here's an overview of the projects
                        you're managing.

                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="manager-projects.php"
                        class="primary-button"
                    >
                        View My Projects
                    </a>

                </div>


            </div>



            <!-- =================================================
                 STAT CARDS
            ================================================== -->

            <div class="stats-grid">


                <!-- TOTAL PROJECTS -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ▣
                    </div>


                    <div class="stat-info">

                        <span>
                            My Projects
                        </span>

                        <strong>
                            <?= $total_projects ?>
                        </strong>

                    </div>


                </div>



                <!-- ACTIVE PROJECTS -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ◉
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



                <!-- COMPLETED PROJECTS -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ✓
                    </div>


                    <div class="stat-info">

                        <span>
                            Completed Projects
                        </span>

                        <strong>
                            <?= $completed_projects ?>
                        </strong>

                    </div>


                </div>



                <!-- PENDING TASKS -->

                <div class="stat-card">


                    <div class="stat-icon">
                        !
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


            </div>



            <!-- =================================================
                 PROJECT + TASK OVERVIEW
            ================================================== -->

            <div class="dashboard-grid">


                <!-- PROJECT OVERVIEW -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Project Overview
                            </h2>

                            <p>
                                Your current project progress
                            </p>

                        </div>


                    </div>


                    <div class="task-overview">


                        <div class="task-stat">

                            <span>
                                Total
                            </span>

                            <strong>
                                <?= $total_projects ?>
                            </strong>

                        </div>


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


                    </div>


                </div>



                <!-- TASK OVERVIEW -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Task Overview
                            </h2>

                            <p>
                                Tasks across your projects
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


                    </div>


                </div>


            </div>



            <!-- =================================================
                 RECENT PROJECTS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Recent Projects
                        </h2>

                        <p>
                            Your most recently created projects
                        </p>

                    </div>


                    <a
                        href="manager-projects.php"
                        class="text-button"
                    >
                        View All
                    </a>


                </div>



                <?php if (!empty($recent_projects)): ?>


                    <div class="table-container">


                        <table class="projects-table">


                            <thead>

                                <tr>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Start Date
                                    </th>

                                    <th>
                                        Deadline
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


                                <?php foreach (
                                    $recent_projects
                                    as $project
                                ): ?>


                                    <tr>


                                        <td>


                                            <div class="project-name">


                                                <div class="project-avatar">

                                                    <?= htmlspecialchars(
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

                                                        <?= htmlspecialchars(
                                                            $project["name"]
                                                        ) ?>

                                                    </strong>


                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $project["description"]
                                                            ?: "No description"
                                                        ) ?>

                                                    </span>

                                                </div>


                                            </div>


                                        </td>



                                        <td>

                                            <?= htmlspecialchars(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $project["start_date"]
                                                    )
                                                )
                                            ) ?>

                                        </td>



                                        <td>

                                            <?php if (
                                                !empty(
                                                    $project["end_date"]
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $project["end_date"]
                                                        )
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

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
                            No Projects Yet
                        </h3>


                        <p>
                            You currently have no projects assigned to you.
                        </p>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 DEADLINES + TASKS
            ================================================== -->

            <div class="dashboard-grid">


                <!-- UPCOMING DEADLINES -->

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



                    <?php if (
                        !empty(
                            $upcoming_deadlines
                        )
                    ): ?>


                        <div class="deadline-list">


                            <?php foreach (
                                $upcoming_deadlines
                                as $deadline
                            ): ?>


                                <?php
                                    $days = (int)($deadline['days_remaining'] ?? 0);
                                    $is_overdue = $days < 0;
                                    $is_due_today = $days === 0;
                                    $is_due_soon = $days > 0 && $days <= 3;
                                    $deadline_class = $is_overdue ? 'overdue' : ($is_due_today ? 'due-today' : ($is_due_soon ? 'due-soon' : ''));
                                ?>

                                <div class="deadline-item clickable-row <?= $deadline_class ?>" onclick="window.location.href='manager-project-details.php?id=<?= (int)$deadline['id'] ?>'">


                                    <div>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $deadline["name"]
                                            ) ?>

                                        </strong>


                                        <span>
                                            <?php if ($is_overdue): ?>
                                                <span class="deadline-overdue">Overdue by <?= abs($days) ?> day<?= abs($days) !== 1 ? 's' : '' ?></span>
                                            <?php elseif ($is_due_today): ?>
                                                <span class="deadline-today">Due today</span>
                                            <?php elseif ($is_due_soon): ?>
                                                <span class="deadline-soon">Due in <?= $days ?> day<?= $days !== 1 ? 's' : '' ?></span>
                                            <?php else: ?>
                                                Due: <?= htmlspecialchars(date('M d, Y', strtotime($deadline['end_date']))) ?>
                                            <?php endif; ?>
                                        </span>


                                    </div>


                                    <span
                                        class="priority-badge priority-<?= htmlspecialchars(
                                            $deadline["priority"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
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



                <!-- RECENT TASKS -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Recent Tasks
                            </h2>

                            <p>
                                Latest tasks from your projects
                            </p>

                        </div>


                        <a
                            href="manager-tasks.php"
                            class="text-button"
                        >
                            View All
                        </a>


                    </div>



                    <?php if (!empty($recent_tasks)): ?>


                        <div class="deadline-list">


                            <?php foreach (
                                $recent_tasks
                                as $task
                            ): ?>


                                <div class="deadline-item">


                                    <div>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $task["title"]
                                            ) ?>

                                        </strong>


                                        <span>

                                            <?= htmlspecialchars(
                                                $task["project_name"]
                                            ) ?>

                                        </span>


                                    </div>


                                    <span
                                        class="status-badge status-<?= htmlspecialchars(
                                            $task["status"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            ucfirst(
                                                str_replace(
                                                    "_",
                                                    " ",
                                                    $task["status"]
                                                )
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
                                No tasks yet.
                            </p>


                        </div>


                    <?php endif; ?>


                </div>


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