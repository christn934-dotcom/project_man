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

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "project_manager"
) {

    header("Location: dashboard.php");
    exit;

}


$manager_id = (int) $_SESSION["user_id"];

$manager_name = $_SESSION["full_name"] ?? "Project Manager";


/*
|--------------------------------------------------------------------------
| GET MANAGER TASKS
|--------------------------------------------------------------------------
|
| We only retrieve tasks that belong to projects
| managed by the currently logged-in project manager.
|
*/

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

        p.id AS project_id,
        p.name AS project_name,

        u.full_name AS member_name

    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    LEFT JOIN users u
        ON t.assigned_to = u.id

    WHERE p.manager_id = ?

    ORDER BY
        CASE
            WHEN t.status != 'completed'
            THEN 0
            ELSE 1
        END,
        t.due_date ASC,
        t.created_at DESC
";


$stmt = mysqli_prepare($conn, $query);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $tasks[] = $row;

        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| TASK COUNTS
|--------------------------------------------------------------------------
*/

$total_tasks = count($tasks);

$pending_tasks = 0;
$in_progress_tasks = 0;
$completed_tasks = 0;
$overdue_tasks = 0;


foreach ($tasks as $task) {

    /*
    |--------------------------------------------------------------
    | Status counts
    |--------------------------------------------------------------
    */

    if ($task["status"] === "completed") {

        $completed_tasks++;

    } elseif ($task["status"] === "in_progress") {

        $in_progress_tasks++;

    } else {

        $pending_tasks++;

    }


    /*
    |--------------------------------------------------------------
    | Overdue tasks
    |--------------------------------------------------------------
    */

    if (
        !empty($task["due_date"]) &&
        $task["due_date"] < date("Y-m-d") &&
        $task["status"] !== "completed"
    ) {

        $overdue_tasks++;

    }

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
        Tasks | PMS
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


        <!-- LOGO -->

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



        <!-- NAVIGATION -->

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
                class="nav-item active"
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



        <!-- LOGOUT -->

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
                        id="taskSearch"
                        placeholder="Search tasks..."
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
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        TASK MANAGEMENT
                    </span>


                    <h1>
                        Tasks
                    </h1>


                    <p>
                        Manage and monitor tasks across your projects.
                    </p>

                </div>                <div class="page-actions">

                    <a href="create-task.php" class="primary-button">+ Create Task</a>

                    <span class="project-count">

                        <?= $total_tasks ?>
                        Task<?= $total_tasks != 1 ? "s" : "" ?>

                    </span>

                </div>


            </div>



            <!-- =================================================
                 TASK STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <!-- TOTAL -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ✓
                    </div>


                    <div class="stat-info">

                        <span>
                            Total Tasks
                        </span>


                        <strong>
                            <?= $total_tasks ?>
                        </strong>

                    </div>


                </div>



                <!-- PENDING -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ◷
                    </div>


                    <div class="stat-info">

                        <span>
                            Pending
                        </span>


                        <strong>
                            <?= $pending_tasks ?>
                        </strong>

                    </div>


                </div>



                <!-- IN PROGRESS -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ↻
                    </div>


                    <div class="stat-info">

                        <span>
                            In Progress
                        </span>


                        <strong>
                            <?= $in_progress_tasks ?>
                        </strong>

                    </div>


                </div>



                <!-- COMPLETED -->

                <div class="stat-card">


                    <div class="stat-icon">
                        ✔
                    </div>


                    <div class="stat-info">

                        <span>
                            Completed
                        </span>


                        <strong>
                            <?= $completed_tasks ?>
                        </strong>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 FILTERS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Project Tasks
                        </h2>


                        <p>
                            Tasks assigned to your projects
                        </p>

                    </div>


                    <div class="task-filters">


                        <select
                            id="statusFilter"
                        >

                            <option value="">
                                All Statuses
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="in_progress">
                                In Progress
                            </option>

                            <option value="completed">
                                Completed
                            </option>

                        </select>



                        <select
                            id="priorityFilter"
                        >

                            <option value="">
                                All Priorities
                            </option>

                            <option value="low">
                                Low
                            </option>

                            <option value="medium">
                                Medium
                            </option>

                            <option value="high">
                                High
                            </option>

                            <option value="urgent">
                                Urgent
                            </option>

                        </select>


                    </div>


                </div>



                <!-- =================================================
                     TASK TABLE
                ================================================== -->

                <?php if (!empty($tasks)): ?>


                    <div class="table-container">


                        <table
                            class="projects-table"
                            id="tasksTable"
                        >


                            <thead>


                                <tr>

                                    <th>
                                        Task
                                    </th>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Assigned To
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>


                            </thead>



                            <tbody>


                                <?php foreach ($tasks as $task): ?>


                                    <?php

                                    $status = $task["status"] ?? "pending";

                                    $priority = $task["priority"] ?? "medium";

                                    $is_overdue =
                                        !empty($task["due_date"]) &&
                                        $task["due_date"] < date("Y-m-d") &&
                                        $status !== "completed";

                                    ?>


                                    <tr
                                        class="task-row"
                                        data-status="<?= htmlspecialchars($status) ?>"
                                        data-priority="<?= htmlspecialchars($priority) ?>"
                                    >


                                        <!-- TASK -->

                                        <td>


                                            <div class="project-name">


                                                <div class="project-avatar">

                                                    <?= htmlspecialchars(
                                                        strtoupper(
                                                            substr(
                                                                $task["title"],
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>

                                                </div>


                                                <div>


                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $task["title"]
                                                        ) ?>

                                                    </strong>


                                                    <?php if (!empty($task["description"])): ?>

                                                        <span>

                                                            <?= htmlspecialchars(
                                                                $task["description"]
                                                            ) ?>

                                                        </span>

                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>



                                        <!-- PROJECT -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $task["project_name"]
                                                ) ?>

                                            </strong>

                                        </td>



                                        <!-- MEMBER -->

                                        <td>

                                            <?php if (!empty($task["member_name"])): ?>

                                                <?= htmlspecialchars(
                                                    $task["member_name"]
                                                ) ?>

                                            <?php else: ?>

                                                <span>
                                                    Unassigned
                                                </span>

                                            <?php endif; ?>

                                        </td>



                                        <!-- DUE DATE -->

                                        <td>


                                            <?php if (!empty($task["due_date"])): ?>


                                                <span
                                                    class="<?= $is_overdue ? 'task-overdue' : '' ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        date(
                                                            "M d, Y",
                                                            strtotime(
                                                                $task["due_date"]
                                                            )
                                                        )
                                                    ) ?>


                                                    <?php if ($is_overdue): ?>

                                                        <small>
                                                            Overdue
                                                        </small>

                                                    <?php endif; ?>


                                                </span>


                                            <?php else: ?>


                                                <span>
                                                    No deadline
                                                </span>


                                            <?php endif; ?>


                                        </td>



                                        <!-- PRIORITY -->

                                        <td>


                                            <span
                                                class="priority-badge priority-<?= htmlspecialchars(
                                                    $priority
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        $priority
                                                    )
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- STATUS -->

                                        <td>


                                            <span
                                                class="status-badge status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $status
                                                        )
                                                    )
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- ACTION -->

                                        <td>


                                            <?php if ($status === "review"): ?>
                                                <div style="display:flex; gap:4px; align-items:center;">
                                                    <form method="POST" action="approve-task.php" style="display:inline;">
                                                        <input type="hidden" name="task_id" value="<?= (int) $task["id"] ?>">
                                                        <input type="hidden" name="approve_action" value="approve">
                                                        <button type="submit" class="table-action" title="Approve Task" style="color:#059669; font-size:16px;">✓</button>
                                                    </form>
                                                    <form method="POST" action="approve-task.php" style="display:inline;">
                                                        <input type="hidden" name="task_id" value="<?= (int) $task["id"] ?>">
                                                        <input type="hidden" name="approve_action" value="reject">
                                                        <button type="submit" class="table-action" title="Send Back" style="color:#dc2626; font-size:16px;">↩</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <a
                                                    href="manager-task-details.php?id=<?= (int) $task["id"] ?>"
                                                    class="table-action"
                                                    title="View Task"
                                                >
                                                    →
                                                </a>
                                            <?php endif; ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div class="empty-state">


                        <div class="empty-icon">
                            ✓
                        </div>


                        <h3>
                            No Tasks Yet
                        </h3>


                        <p>
                            There are currently no tasks assigned to your projects.
                        </p>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 OVERDUE NOTICE
            ================================================== -->

            <?php if ($overdue_tasks > 0): ?>


                <div class="dashboard-card">


                    <div class="card-header">


                        <div>


                            <h2>
                                Attention Required
                            </h2>


                            <p>

                                You have
                                <strong>
                                    <?= $overdue_tasks ?>
                                </strong>

                                overdue task<?= $overdue_tasks != 1 ? "s" : "" ?>.

                            </p>


                        </div>


                    </div>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>



<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| TASK SEARCH + FILTER
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById("taskSearch");

const statusFilter =
    document.getElementById("statusFilter");

const priorityFilter =
    document.getElementById("priorityFilter");


function filterTasks() {


    const search =
        searchInput
            ? searchInput.value
                .toLowerCase()
                .trim()
            : "";


    const selectedStatus =
        statusFilter
            ? statusFilter.value
            : "";


    const selectedPriority =
        priorityFilter
            ? priorityFilter.value
            : "";


    const rows =
        document.querySelectorAll(
            ".task-row"
        );


    rows.forEach(function(row) {


        const rowText =
            row.textContent
                .toLowerCase();


        const rowStatus =
            row.dataset.status;


        const rowPriority =
            row.dataset.priority;


        const matchesSearch =
            rowText.includes(search);


        const matchesStatus =
            selectedStatus === "" ||
            rowStatus === selectedStatus;


        const matchesPriority =
            selectedPriority === "" ||
            rowPriority === selectedPriority;


        if (
            matchesSearch &&
            matchesStatus &&
            matchesPriority
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }


    });


}


/*
|--------------------------------------------------------------------------
| SEARCH EVENT
|--------------------------------------------------------------------------
*/

if (searchInput) {

    searchInput.addEventListener(
        "input",
        filterTasks
    );

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (statusFilter) {

    statusFilter.addEventListener(
        "change",
        filterTasks
    );

}


/*
|--------------------------------------------------------------------------
| PRIORITY FILTER
|--------------------------------------------------------------------------
*/

if (priorityFilter) {

    priorityFilter.addEventListener(
        "change",
        filterTasks
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