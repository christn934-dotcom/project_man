<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: dashboard.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$admin_name = $_SESSION["full_name"] ?? "Administrator";


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
        Admin Dashboard | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


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
                href="admin-dashboard.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Projects

            </a>


            <a
                href="#"
                class="nav-item"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Tasks

            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="#"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="#"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♚
                </span>

                Project Managers

            </a>


            <a
                href="reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

            </a>


            <p class="nav-title">
                SYSTEM
            </p>


            <a
                href="#"
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
                    class="notification-button"
                    type="button"
                >
                    ♧
                </button>


                <div class="admin-profile">


                    <div class="profile-avatar">

                        <?= htmlspecialchars(
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

                            <?= htmlspecialchars(
                                $admin_name
                            ) ?>

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



        <!-- =====================================================
             DASHBOARD CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        ADMINISTRATION
                    </span>


                    <h1>
                        Welcome back,
                        <?= htmlspecialchars(
                            $admin_name
                        ) ?>!
                    </h1>


                    <p>
                        Here's an overview of your project management system.
                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="projects.php"
                        class="primary-button"
                    >
                        + New Project
                    </a>

                </div>


            </div>



            <!-- =================================================
                 STAT CARDS
            ================================================== -->

            <div class="stats-grid">


                <!-- USERS -->

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


                <!-- MANAGERS -->

                <div class="stat-card">

                    <div class="stat-icon">
                        ♚
                    </div>

                    <div class="stat-info">

                        <span>
                            Project Managers
                        </span>

                        <strong>
                            <?= $total_managers ?>
                        </strong>

                    </div>

                </div>


                <!-- MEMBERS -->

                <div class="stat-card">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-info">

                        <span>
                            Team Members
                        </span>

                        <strong>
                            <?= $total_members ?>
                        </strong>

                    </div>

                </div>


                <!-- PROJECTS -->

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


            </div>



            <!-- =================================================
                 PROJECT / TASK OVERVIEW
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


                </div>



                <!-- TASK OVERVIEW -->

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


                <?php if (
                    count($recent_projects) > 0
                ): ?>


                    <div class="table-container">


                        <table class="projects-table">


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


                                <?php foreach (
                                    $recent_projects
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

                                            <?= htmlspecialchars(
                                                $project["start_date"]
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
                            Create your first project to get started.
                        </p>


                        <a
                            href="projects.php"
                            class="primary-button"
                        >
                            + Create Project
                        </a>

                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 DEADLINES + ACTIVITY
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
                        count($upcoming_deadlines) > 0
                    ): ?>


                        <div class="deadline-list">


                            <?php foreach (
                                $upcoming_deadlines
                                as $deadline
                            ): ?>


                                <div class="deadline-item">


                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $deadline["name"]
                                            ) ?>

                                        </strong>


                                        <span>

                                            Due:
                                            <?= htmlspecialchars(
                                                $deadline["end_date"]
                                            ) ?>

                                        </span>

                                    </div>


                                    <span
                                        class="priority-badge priority-<?= htmlspecialchars(
                                            $deadline["priority"]
                                        ) ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars(
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



                <!-- RECENT ACTIVITY -->

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


            </div>


        </section>


    </main>


</div>


</body>

</html>