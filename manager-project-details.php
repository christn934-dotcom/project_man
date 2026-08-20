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

if ($_SESSION["role"] !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Project ID
|--------------------------------------------------------------------------
*/

$project_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($project_id <= 0) {
    header("Location: manager-projects.php");
    exit;
}


$manager_id = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Get Project
|--------------------------------------------------------------------------
|
| IMPORTANT:
| manager_id = ? ensures that a manager can only
| open projects assigned to themselves.
|
*/

$query = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.start_date,
        p.end_date,
        p.status,
        p.priority,
        p.created_at,

        u.full_name AS manager_name,
        u.email AS manager_email

    FROM projects p

    INNER JOIN users u
        ON p.manager_id = u.id

    WHERE p.id = ?
    AND p.manager_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $project_id,
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$project = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Project not found
|--------------------------------------------------------------------------
*/

if (!$project) {
    header("Location: manager-projects.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Team Members
|--------------------------------------------------------------------------
*/

$members = [];


$query = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.status

    FROM project_members pm

    INNER JOIN users u
        ON pm.user_id = u.id

    WHERE pm.project_id = ?

    ORDER BY u.full_name ASC
";


$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result)) {

    $members[] = $row;

}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Task Statistics
|--------------------------------------------------------------------------
*/

$total_tasks = 0;
$todo_tasks = 0;
$progress_tasks = 0;
$review_tasks = 0;
$completed_tasks = 0;


/*
| Total Tasks
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$total_tasks = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);


/*
| To Do
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    AND status = 'todo'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$todo_tasks = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);


/*
| In Progress
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    AND status = 'in_progress'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$progress_tasks = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);


/*
| Review
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    AND status = 'review'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$review_tasks = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);


/*
| Completed
*/

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    AND status = 'completed'
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

$completed_tasks = (int) ($row["total"] ?? 0);

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Calculate Progress
|--------------------------------------------------------------------------
*/

$progress = 0;

if ($total_tasks > 0) {

    $progress = round(
        ($completed_tasks / $total_tasks) * 100
    );

}


/*
|--------------------------------------------------------------------------
| Get Tasks
|--------------------------------------------------------------------------
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
        u.full_name AS assigned_name

    FROM tasks t

    LEFT JOIN users u
        ON t.assigned_to = u.id

    WHERE t.project_id = ?

    ORDER BY t.due_date ASC
";


$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $project_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result)) {

    $tasks[] = $row;

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
        <?= htmlspecialchars($project["name"]) ?>
        | PMS
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
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="manager-projects.php"
                class="nav-item active"
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
                                    $_SESSION["full_name"],
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $_SESSION["full_name"]
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


            <!-- BACK BUTTON -->

            <a
                href="manager-projects.php"
                class="back-button"
            >
                ← Back to My Projects
            </a>


            <!-- PROJECT HEADER -->

            <div class="project-details-header">

                <div>

                    <span class="page-label">
                        PROJECT
                    </span>

                    <h1>

                        <?= htmlspecialchars(
                            $project["name"]
                        ) ?>

                    </h1>

                    <p>

                        <?= htmlspecialchars(
                            $project["description"]
                            ?: "No description provided."
                        ) ?>

                    </p>

                </div>


                <div class="project-header-status">

                    <span
                        class="
                            status-badge
                            status-<?=
                                htmlspecialchars(
                                    $project["status"]
                                )
                        "
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


                    <span
                        class="
                            priority-badge
                            priority-<?=
                                htmlspecialchars(
                                    $project["priority"]
                                )
                        "
                    >

                        <?= htmlspecialchars(
                            ucfirst(
                                $project["priority"]
                            )
                        ) ?>

                    </span>

                </div>

            </div>


            <!-- =================================================
                 PROJECT INFORMATION
            ================================================== -->

            <div class="project-info-grid">


                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                Project Information
                            </h2>

                            <p>
                                Basic project details
                            </p>

                        </div>

                    </div>


                    <div class="project-info-list">


                        <div>

                            <span>
                                Start Date
                            </span>

                            <strong>

                                <?= date(
                                    "M d, Y",
                                    strtotime(
                                        $project["start_date"]
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                Deadline
                            </span>

                            <strong>

                                <?php if (
                                    !empty(
                                        $project["end_date"]
                                    )
                                ): ?>

                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $project["end_date"]
                                        )
                                    ) ?>

                                <?php else: ?>

                                    No deadline

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                Project Manager
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $project["manager_name"]
                                ) ?>

                            </strong>

                        </div>


                        <div>

                            <span>
                                Manager Email
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $project["manager_email"]
                                ) ?>

                            </strong>

                        </div>


                    </div>

                </div>


                <!-- TEAM -->

                <div class="dashboard-card">

                    <div class="card-header">

                        <div>

                            <h2>
                                Team Members
                            </h2>

                            <p>

                                <?= count($members) ?>

                                member<?=
                                    count($members) != 1
                                        ? "s"
                                        : ""
                                ?>

                            </p>

                        </div>

                    </div>


                    <?php if (!empty($members)): ?>


                        <div class="team-member-list">


                            <?php foreach (
                                $members
                                as $member
                            ): ?>


                                <div class="team-member-item">


                                    <div class="member-avatar">

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                substr(
                                                    $member["full_name"],
                                                    0,
                                                    1
                                                )
                                            )
                                        ) ?>

                                    </div>


                                    <div class="member-info">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $member["full_name"]
                                            ) ?>

                                        </strong>

                                        <span>

                                            <?= htmlspecialchars(
                                                $member["email"]
                                            ) ?>

                                        </span>

                                    </div>


                                    <span
                                        class="
                                            member-status
                                            <?= $member["status"] === "active"
                                                ? "active"
                                                : "inactive"
                                            ?>
                                    ">

                                        <?= ucfirst(
                                            $member["status"]
                                        ) ?>

                                    </span>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="small-empty-state">

                            No team members assigned.

                        </div>


                    <?php endif; ?>


                </div>


            </div>


            <!-- =================================================
                 TASK PROGRESS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Task Progress
                        </h2>

                        <p>
                            Current project task progress
                        </p>

                    </div>


                    <strong>
                        <?= $progress ?>%
                    </strong>

                </div>


                <div class="progress-container">

                    <div
                        class="progress-bar"
                        style="
                            width: <?= $progress ?>%;
                        "
                    ></div>

                </div>


                <div class="task-stat-row">


                    <div>

                        <span class="task-stat-number">
                            <?= $total_tasks ?>
                        </span>

                        <span>
                            Total
                        </span>

                    </div>


                    <div>

                        <span class="task-stat-number">
                            <?= $todo_tasks ?>
                        </span>

                        <span>
                            To Do
                        </span>

                    </div>


                    <div>

                        <span class="task-stat-number">
                            <?= $progress_tasks ?>
                        </span>

                        <span>
                            In Progress
                        </span>

                    </div>


                    <div>

                        <span class="task-stat-number">
                            <?= $review_tasks ?>
                        </span>

                        <span>
                            Review
                        </span>

                    </div>


                    <div>

                        <span class="task-stat-number">
                            <?= $completed_tasks ?>
                        </span>

                        <span>
                            Completed
                        </span>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 TASKS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">

                    <div>

                        <h2>
                            Project Tasks
                        </h2>

                        <p>
                            Tasks belonging to this project
                        </p>

                    </div>


                    <a
                        href="manager-tasks.php?project_id=<?= $project_id ?>"
                        class="primary-button"
                    >
                        + Add Task
                    </a>

                </div>


                <?php if (!empty($tasks)): ?>


                    <div class="table-container">

                        <table class="projects-table">

                            <thead>

                                <tr>

                                    <th>
                                        Task
                                    </th>

                                    <th>
                                        Assigned To
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Due Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $tasks
                                as $task
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $task["title"]
                                            ) ?>

                                        </strong>

                                        <?php if (
                                            !empty(
                                                $task["description"]
                                            )
                                        ): ?>

                                            <small
                                                style="
                                                    display:block;
                                                    color:#9ca3af;
                                                    margin-top:4px;
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    $task["description"]
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $task["assigned_name"]
                                            ?? "Unassigned"
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                priority-badge
                                                priority-<?=
                                                    htmlspecialchars(
                                                        $task["priority"]
                                                    )
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $task["priority"]
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $task["due_date"]
                                            )
                                        ): ?>

                                            <?= date(
                                                "M d, Y",
                                                strtotime(
                                                    $task["due_date"]
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            —

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                status-badge
                                                status-<?=
                                                    htmlspecialchars(
                                                        $task["status"]
                                                    )
                                            "
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

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            ✓
                        </div>

                        <h3>
                            No Tasks Yet
                        </h3>

                        <p>
                            This project doesn't have any
                            tasks yet.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>

    </main>

</div>

</body>

</html>