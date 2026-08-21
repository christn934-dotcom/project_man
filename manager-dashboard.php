<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| PROJECT MANAGER PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "project_manager"
) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| MANAGER INFORMATION
|--------------------------------------------------------------------------
*/

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
    WHERE manager_id = $manager_id
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
    FROM projects
    WHERE manager_id = $manager_id
    AND status = 'in_progress'
";

$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $active_projects = (int) $row["total"];
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
    WHERE manager_id = $manager_id
    AND status = 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $completed_projects = (int) $row["total"];
}


/*
|--------------------------------------------------------------------------
| TOTAL TASKS
|--------------------------------------------------------------------------
*/

$total_tasks = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = $manager_id
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
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = $manager_id
    AND t.status != 'completed'
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
    FROM tasks t
    INNER JOIN projects p
        ON t.project_id = p.id
    WHERE p.manager_id = $manager_id
    AND t.status = 'completed'
";

$result = mysqli_query($conn, $query);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $completed_tasks = (int) $row["total"];
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
        status
    FROM projects
    WHERE manager_id = $manager_id
    AND end_date IS NOT NULL
    AND end_date >= CURDATE()
    AND status != 'completed'
    ORDER BY end_date ASC
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
| RECENT PROJECTS
|--------------------------------------------------------------------------
*/

$recent_projects = [];

$query = "
    SELECT
        id,
        name,
        description,
        start_date,
        end_date,
        priority,
        status
    FROM projects
    WHERE manager_id = $manager_id
    ORDER BY created_at DESC
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
    WHERE p.manager_id = $manager_id
    ORDER BY t.created_at DESC
    LIMIT 5
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $recent_tasks[] = $row;
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
    WHERE
        a.project_id IN (
            SELECT id
            FROM projects
            WHERE manager_id = $manager_id
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
    substr($manager_name, 0, 2)
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
        Manager Dashboard | PMS
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
                MANAGEMENT
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

                        <?= htmlspecialchars($initials) ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars($manager_name) ?>

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
             DASHBOARD
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        PROJECT MANAGEMENT
                    </span>


                    <h1>

                        Welcome back,
                        <?= htmlspecialchars($manager_name) ?>!

                    </h1>


                    <p>
                        Here's an overview of the projects you manage.
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
                                Current status of your projects
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


            </div>



            <!-- =================================================
                 RECENT PROJECTS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            My Recent Projects
                        </h2>

                        <p>
                            Recently created projects assigned to you
                        </p>

                    </div>


                    <a
                        href="manager-projects.php"
                        class="text-button"
                    >
                        View All
                    </a>


                </div>


                <?php if (count($recent_projects) > 0): ?>


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


                                                    <?php if (
                                                        !empty(
                                                            $project["description"]
                                                        )
                                                    ): ?>

                                                        <span>

                                                            <?= htmlspecialchars(
                                                                $project["description"]
                                                            ) ?>

                                                        </span>

                                                    <?php endif; ?>


                                                </div>


                                            </div>

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
                            No projects assigned
                        </h3>

                        <p>
                            You don't have any projects assigned to you yet.
                        </p>

                    </div>


                <?php endif; ?>


            </div>



            <!-- =================================================
                 DEADLINES + RECENT TASKS
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



                <!-- RECENT TASKS -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Recent Tasks
                            </h2>

                            <p>
                                Latest tasks in your projects
                            </p>

                        </div>


                        <a
                            href="manager-tasks.php"
                            class="text-button"
                        >
                            View All
                        </a>


                    </div>


                    <?php if (count($recent_tasks) > 0): ?>


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
                                No tasks yet.
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
                            Latest activity in your projects
                        </p>

                    </div>


                </div>


                <?php if (count($activities) > 0): ?>


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


</body>

</html>