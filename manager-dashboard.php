<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| Authentication
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


$manager_id = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Manager Information
|--------------------------------------------------------------------------
*/

$manager_name = $_SESSION["full_name"] ?? "Project Manager";


/*
|--------------------------------------------------------------------------
| Total Projects
|--------------------------------------------------------------------------
*/

$total_projects = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $total_projects = $row["total"];

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| In Progress Projects
|--------------------------------------------------------------------------
*/

$in_progress = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND status = 'in_progress'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $in_progress = $row["total"];

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Completed Projects
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

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $completed_projects = $row["total"];

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| On Hold Projects
|--------------------------------------------------------------------------
*/

$on_hold = 0;

$query = "
    SELECT COUNT(*) AS total
    FROM projects
    WHERE manager_id = ?
    AND status = 'on_hold'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $on_hold = $row["total"];

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Task Statistics
|--------------------------------------------------------------------------
*/

$total_tasks = 0;
$completed_tasks = 0;
$pending_tasks = 0;


/*
| Total tasks
*/

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE p.manager_id = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $total_tasks = $row["total"];

}

mysqli_stmt_close($stmt);


/*
| Completed tasks
*/

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE p.manager_id = ?
    AND t.status = 'completed'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $completed_tasks = $row["total"];

}

mysqli_stmt_close($stmt);


/*
| Pending tasks
*/

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE p.manager_id = ?
    AND t.status != 'completed'
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    $pending_tasks = $row["total"];

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Upcoming Deadlines
|--------------------------------------------------------------------------
*/

$upcoming_projects = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.end_date,
        p.priority,
        p.status

    FROM projects p

    WHERE p.manager_id = ?

    AND p.end_date IS NOT NULL

    AND p.end_date >= CURDATE()

    AND p.status != 'completed'

    ORDER BY p.end_date ASC

    LIMIT 5
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {

    $upcoming_projects[] = $row;

}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Recent Projects
|--------------------------------------------------------------------------
*/

$projects = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.start_date,
        p.end_date,
        p.status,
        p.priority

    FROM projects p

    WHERE p.manager_id = ?

    ORDER BY p.created_at DESC

    LIMIT 5
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {

    $projects[] = $row;

}

mysqli_stmt_close($stmt);

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
                href="manager-team.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                My Team

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
         MAIN
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
                                    $manager_name,
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


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
             CONTENT
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
                        <?= htmlspecialchars(
                            $manager_name
                        ) ?>!
                    </h1>

                    <p>
                        Here's what's happening with your projects.
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
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <!-- TOTAL PROJECTS -->

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


                <!-- IN PROGRESS -->

                <div class="stat-card">

                    <div class="stat-icon">
                        ◷
                    </div>

                    <div class="stat-info">

                        <span>
                            In Progress
                        </span>

                        <strong>
                            <?= $in_progress ?>
                        </strong>

                    </div>

                </div>


                <!-- COMPLETED -->

                <div class="stat-card">

                    <div class="stat-icon">
                        ✓
                    </div>

                    <div class="stat-info">

                        <span>
                            Completed
                        </span>

                        <strong>
                            <?= $completed_projects ?>
                        </strong>

                    </div>

                </div>


                <!-- ON HOLD -->

                <div class="stat-card">

                    <div class="stat-icon">
                        !
                    </div>

                    <div class="stat-info">

                        <span>
                            On Hold
                        </span>

                        <strong>
                            <?= $on_hold ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 TASK STATISTICS
            ================================================== -->

            <div class="dashboard-grid">


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
                                Total Tasks
                            </span>

                            <strong>
                                <?= $total_tasks ?>
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
                                Pending
                            </span>

                            <strong>
                                <?= $pending_tasks ?>
                            </strong>

                        </div>

                    </div>

                </div>


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
                        count($upcoming_projects) > 0
                    ): ?>


                        <div class="deadline-list">


                            <?php foreach (
                                $upcoming_projects
                                as $project
                            ): ?>

                                <div class="deadline-item">

                                    <div>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $project["name"]
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $project["end_date"]
                                            ) ?>

                                        </span>

                                    </div>


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
                            Projects assigned to you as manager
                        </p>

                    </div>


                    <a
                        href="manager-projects.php"
                        class="text-button"
                    >
                        View All
                    </a>

                </div>


                <?php if (
                    count($projects) > 0
                ): ?>


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
                                    $projects
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


                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $project["description"] ?? ""
                                                        ) ?>

                                                    </span>

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
                            You currently have no projects assigned to you.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>

    </main>

</div>

</body>

</html>