<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
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
        status
    FROM projects
    WHERE manager_id = ?
    AND end_date IS NOT NULL
    AND end_date >= CURDATE()
    AND status != 'completed'
    ORDER BY end_date ASC
    LIMIT 5
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
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $deadline["end_date"]
                                                    )
                                                )
                                            ) ?>

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


</body>

</html>