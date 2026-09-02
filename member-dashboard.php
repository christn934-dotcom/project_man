<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| MEMBER PROTECTION
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


if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "member"
) {

    header("Location: dashboard.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| MEMBER INFORMATION
|--------------------------------------------------------------------------
*/

$member_id = (int) $_SESSION["user_id"];

$member_name = $_SESSION["full_name"] ?? "Team Member";

$member_email = $_SESSION["email"] ?? "";


/*
|--------------------------------------------------------------------------
| TOTAL PROJECTS
|--------------------------------------------------------------------------
| Projects where the logged-in member is a team member.
|--------------------------------------------------------------------------
*/

$total_projects = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM project_members
    WHERE user_id = $member_id
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_projects = (int) $row["total"];

}


/*
|--------------------------------------------------------------------------
| ACTIVE PROJECTS
|--------------------------------------------------------------------------
*/

$active_projects = 0;

$query = "
    SELECT COUNT(*) AS total

    FROM project_members pm

    INNER JOIN projects p
        ON pm.project_id = p.id

    WHERE pm.user_id = $member_id

    AND p.status = 'in_progress'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $active_projects = (int) $row["total"];

}


/*
|--------------------------------------------------------------------------
| TOTAL ASSIGNED TASKS
|--------------------------------------------------------------------------
*/

$total_tasks = 0;

$query = "
    SELECT COUNT(*) AS total

    FROM tasks

    WHERE assigned_to = $member_id
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_tasks = (int) $row["total"];

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

    WHERE assigned_to = $member_id

    AND status != 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $pending_tasks = (int) $row["total"];

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

    WHERE assigned_to = $member_id

    AND status = 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $completed_tasks = (int) $row["total"];

}


/*
|--------------------------------------------------------------------------
| UPCOMING TASK DEADLINES
|--------------------------------------------------------------------------
*/

$upcoming_tasks = [];

$query = "
    SELECT

        t.id,
        t.title,
        t.due_date,
        t.priority,
        t.status,

        p.name AS project_name

    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE t.assigned_to = $member_id

    AND t.due_date IS NOT NULL

    AND t.due_date >= CURDATE()

    AND t.status != 'completed'

    ORDER BY t.due_date ASC

    LIMIT 5
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $upcoming_tasks[] = $row;

    }

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

    WHERE t.assigned_to = $member_id

    ORDER BY t.created_at DESC

    LIMIT 6
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $recent_tasks[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| MY PROJECTS
|--------------------------------------------------------------------------
*/

$my_projects = [];

$query = "
    SELECT

        p.id,
        p.name,
        p.description,
        p.start_date,
        p.end_date,
        p.priority,
        p.status,

        u.full_name AS manager_name

    FROM project_members pm

    INNER JOIN projects p
        ON pm.project_id = p.id

    INNER JOIN users u
        ON p.manager_id = u.id

    WHERE pm.user_id = $member_id

    ORDER BY p.created_at DESC

    LIMIT 5
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $my_projects[] = $row;

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

    WHERE a.project_id IN (

        SELECT project_id

        FROM project_members

        WHERE user_id = $member_id

    )

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
| INITIALS
|--------------------------------------------------------------------------
*/

$initials = strtoupper(
    substr($member_name, 0, 2)
);

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
        Member Dashboard | PMS
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
                href="member-dashboard.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="member-projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                My Projects

            </a>


            <a
                href="member-tasks.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ✓
                </span>

                My Tasks

            </a>


            <p class="nav-title">
                COLLABORATION
            </p>


            <a
                href="team.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Team

            </a>


            <a
                href="notifications.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♧
                </span>

                Notifications

            </a>


            <p class="nav-title">
                SYSTEM
            </p>


            <a
                href="member-settings.php"
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


                    <?= render_avatar($_SESSION["profile_image"] ?? null, $member_name, (int)($_SESSION["user_id"])) ?>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $member_name
                            ) ?>

                        </strong>

                        <span>
                            Team Member
                        </span>

                    </div>


                    <span class="profile-arrow">
                        ▾
                    </span>


                </div>


            </div>


        </header>



        <!-- =====================================================
             DASHBOARD CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        TEAM MEMBER
                    </span>


                    <h1>

                        Welcome back,
                        <?= htmlspecialchars(
                            $member_name
                        ) ?>!

                    </h1>


                    <p>
                        Here's an overview of your projects and assigned tasks.
                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="member-tasks.php"
                        class="primary-button"
                    >
                        View My Tasks
                    </a>

                </div>


            </div>



            <!-- =================================================
                 STAT CARDS
            ================================================== -->

            <div class="stats-grid">


                <!-- PROJECTS -->

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



                <!-- PENDING TASKS -->

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



                <!-- COMPLETED TASKS -->

                <div class="stat-card">

                    <div class="stat-icon">
                        ★
                    </div>


                    <div class="stat-info">

                        <span>
                            Completed Tasks
                        </span>


                        <strong>
                            <?= $completed_tasks ?>
                        </strong>

                    </div>

                </div>


            </div>



            <!-- =================================================
                 TASK OVERVIEW
            ================================================== -->

            <div class="dashboard-grid">


                <!-- TASK OVERVIEW -->

                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                My Task Overview
                            </h2>

                            <p>
                                Your current task progress
                            </p>

                        </div>

                    </div>


                    <div class="task-overview">


                        <div class="task-stat">

                            <span>
                                Total
                            </span>


                            <strong>
                                <?= $total_tasks ?>
                            </strong>

                        </div>


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



                <!-- PROJECT OVERVIEW -->

                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Project Overview
                            </h2>

                            <p>
                                Projects you belong to
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


                    </div>


                </div>


            </div>



            <!-- =================================================
                 MY PROJECTS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            My Projects
                        </h2>


                        <p>
                            Projects you are currently part of
                        </p>

                    </div>


                    <a
                        href="member-projects.php"
                        class="text-button"
                    >
                        View All
                    </a>


                </div>


                <?php if (
                    count($my_projects) > 0
                ): ?>


                    <div class="table-container">


                        <table class="projects-table">


                            <thead>

                                <tr>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Project Manager
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


                                <?php foreach (
                                    $my_projects
                                    as $project
                                ): ?>


                                    <tr>


                                        <td>


                                            <div class="project-name">


                                                <div class="project-avatar">

                                                    <?= strtoupper(
                                                        substr(
                                                            $project["name"],
                                                            0,
                                                            1
                                                        )
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $project["name"]
                                                        ) ?>

                                                    </strong>


                                                </div>


                                            </div>


                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $project["manager_name"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= $project["end_date"]
                                                ? htmlspecialchars(
                                                    $project["end_date"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <span
                                                class="priority-badge priority-<?= htmlspecialchars(
                                                    $project["priority"]
                                                ) ?>"
                                            >

                                                <?= ucfirst(
                                                    htmlspecialchars(
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

                                                <?= ucfirst(
                                                    str_replace(
                                                        "_",
                                                        " ",
                                                        htmlspecialchars(
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
                            You have not been added to any projects yet.
                        </p>


                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 UPCOMING TASKS + RECENT TASKS
            ================================================== -->

            <div class="dashboard-grid">


                <!-- UPCOMING TASK DEADLINES -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Upcoming Deadlines
                            </h2>


                            <p>
                                Your upcoming task deadlines
                            </p>

                        </div>

                    </div>


                    <?php if (
                        count($upcoming_tasks) > 0
                    ): ?>


                        <div class="deadline-list">


                            <?php foreach (
                                $upcoming_tasks
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

                                            —

                                            Due:
                                            <?= htmlspecialchars(
                                                $task["due_date"]
                                            ) ?>

                                        </span>


                                    </div>


                                    <span
                                        class="priority-badge priority-<?= htmlspecialchars(
                                            $task["priority"]
                                        ) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars(
                                                $task["priority"]
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
                                No upcoming task deadlines.
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
                                Your recently assigned tasks
                            </p>

                        </div>


                        <a
                            href="member-tasks.php"
                            class="text-button"
                        >
                            View All
                        </a>


                    </div>


                    <?php if (
                        count($recent_tasks) > 0
                    ): ?>


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

                                        <?= ucfirst(
                                            str_replace(
                                                "_",
                                                " ",
                                                htmlspecialchars(
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
                                No tasks have been assigned to you yet.
                            </p>


                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- =================================================
                 RECENT ACTIVITY
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Recent Activity
                        </h2>


                        <p>
                            Recent activity from your projects
                        </p>

                    </div>


                </div>


                <?php if (
                    count($activities) > 0
                ): ?>


                    <div class="activity-list">


                        <?php foreach (
                            $activities
                            as $activity
                        ): ?>


                            <div class="activity-item">


                                <div class="activity-icon">
                                    •
                                </div>


                                <div>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $activity["full_name"]
                                        ) ?>

                                    </strong>


                                    <p>

                                        <?= htmlspecialchars(
                                            $activity["description"]
                                        ) ?>

                                    </p>


                                    <small>

                                        <?= htmlspecialchars(
                                            $activity["created_at"]
                                        ) ?>

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