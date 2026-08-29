<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";

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
| GET PROJECTS
|--------------------------------------------------------------------------
*/

$projects = [];

$query = "
    SELECT
        id,
        name
    FROM projects
    ORDER BY name ASC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $projects[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| GET TEAM MEMBERS
|--------------------------------------------------------------------------
|
| Tasks can be assigned to members or project managers.
| We exclude the Admin.
|--------------------------------------------------------------------------
*/

$users = [];

$query = "
    SELECT
        id,
        full_name,
        email,
        role
    FROM users
    WHERE role IN ('member', 'project_manager')
      AND status = 'active'
    ORDER BY full_name ASC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $users[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| GET TASKS
|--------------------------------------------------------------------------
*/

$tasks = [];

$query = "
    SELECT
        t.id,
        t.project_id,
        t.title,
        t.description,
        t.assigned_to,
        t.due_date,
        t.priority,
        t.status,
        t.estimated_hours,
        t.actual_hours,
        t.created_at,

        p.name AS project_name,

        u.full_name AS assigned_name

    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    LEFT JOIN users u
        ON t.assigned_to = u.id

    ORDER BY t.created_at DESC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $tasks[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| TASK COUNTS
|--------------------------------------------------------------------------
*/

$total_tasks = count($tasks);

$todo_tasks = 0;
$in_progress_tasks = 0;
$review_tasks = 0;
$completed_tasks = 0;

foreach ($tasks as $task) {

    switch ($task["status"]) {

        case "to_do":
            $todo_tasks++;
            break;

        case "in_progress":
            $in_progress_tasks++;
            break;

        case "review":
            $review_tasks++;
            break;

        case "completed":
            $completed_tasks++;
            break;

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


    <style>

        /*
        |--------------------------------------------------------------------------
        | TASK PAGE
        |--------------------------------------------------------------------------
        */

        .task-stat-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

            margin-bottom: 25px;

        }


        .task-mini-card {

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 20px;

        }


        .task-mini-card span {

            display: block;

            color: #6b7280;

            font-size: 13px;

            margin-bottom: 8px;

        }


        .task-mini-card strong {

            font-size: 25px;

            color: #111827;

        }


        .task-table-title {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .task-title-cell {

            max-width: 280px;

        }


        .task-title-cell strong {

            display: block;

            color: #111827;

        }


        .task-title-cell span {

            display: block;

            margin-top: 4px;

            color: #9ca3af;

            font-size: 12px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .task-project {

            font-size: 13px;

            color: #374151;

            font-weight: 500;

        }


        .task-assignee {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .task-avatar {

            width: 32px;

            height: 32px;

            border-radius: 50%;

            background: #f3f4f6;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 700;

        }


        .task-due {

            font-size: 13px;

            color: #4b5563;

        }


        .task-due.overdue {

            color: #dc2626;

            font-weight: 600;

        }


        .task-actions {

            display: flex;

            gap: 6px;

        }


        .task-action-button {

            border: 1px solid #e5e7eb;

            background: #ffffff;

            border-radius: 7px;

            padding: 7px 10px;

            cursor: pointer;

            text-decoration: none;

            color: #374151;

            font-size: 13px;

        }


        .task-action-button:hover {

            background: #f9fafb;

        }


        .delete-task {

            color: #dc2626;

        }


        .empty-task {

            text-align: center;

            padding: 60px 20px;

        }


        .empty-task-icon {

            font-size: 45px;

            margin-bottom: 15px;

        }


        .empty-task h3 {

            margin-bottom: 8px;

        }


        .empty-task p {

            color: #6b7280;

            margin-bottom: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .modal-overlay {

            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, 0.45);

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

            z-index: 9999;

        }


        .modal-overlay.show {

            display: flex;

        }


        .modal {

            background: #ffffff;

            width: 100%;

            max-width: 650px;

            max-height: 90vh;

            overflow-y: auto;

            border-radius: 14px;

            padding: 28px;

        }


        .modal-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            margin-bottom: 25px;

        }


        .modal-header h2 {

            margin: 0 0 5px;

        }


        .modal-header p {

            margin: 0;

            color: #6b7280;

            font-size: 14px;

        }


        .modal-close {

            border: none;

            background: transparent;

            font-size: 25px;

            cursor: pointer;

            color: #6b7280;

        }


        .form-group {

            margin-bottom: 18px;

        }


        .form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 600;

            color: #374151;

        }


        .form-group input,

        .form-group textarea,

        .form-group select {

            width: 100%;

            padding: 11px 13px;

            border: 1px solid #d1d5db;

            border-radius: 8px;

            font-family: inherit;

            font-size: 14px;

            box-sizing: border-box;

        }


        .form-group textarea {

            resize: vertical;

        }


        .form-group input:focus,

        .form-group textarea:focus,

        .form-group select:focus {

            outline: none;

            border-color: #111827;

        }


        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 16px;

        }


        .modal-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

        }


        .secondary-button {

            border: 1px solid #d1d5db;

            background: #ffffff;

            color: #374151;

            padding: 10px 18px;

            border-radius: 8px;

            cursor: pointer;

            text-decoration: none;

        }


        @media (max-width: 900px) {

            .task-stat-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 600px) {

            .task-stat-grid {

                grid-template-columns: 1fr;

            }


            .form-row {

                grid-template-columns: 1fr;

            }


            .modal {

                padding: 20px;

            }

        }

    </style>

</head>


<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
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
                href="admin-dashboard.php"
                class="nav-item"
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
                href="tasks.php"
                class="nav-item active"
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
                href="users.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="users.php?role=project_manager"
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
             PAGE
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        MANAGEMENT
                    </span>


                    <h1>
                        Tasks
                    </h1>


                    <p>
                        Create, assign and manage project tasks.
                    </p>

                </div>


                <div class="page-actions">

                    <button
                        type="button"
                        class="primary-button"
                        onclick="openTaskModal()"
                    >
                        + New Task
                    </button>

                </div>


            </div>



            <!-- =================================================
                 TASK STATISTICS
            ================================================== -->

            <div class="task-stat-grid">


                <div class="task-mini-card">

                    <span>
                        Total Tasks
                    </span>

                    <strong>
                        <?= $total_tasks ?>
                    </strong>

                </div>


                <div class="task-mini-card">

                    <span>
                        To Do
                    </span>

                    <strong>
                        <?= $todo_tasks ?>
                    </strong>

                </div>


                <div class="task-mini-card">

                    <span>
                        In Progress
                    </span>

                    <strong>
                        <?= $in_progress_tasks ?>
                    </strong>

                </div>


                <div class="task-mini-card">

                    <span>
                        Completed
                    </span>

                    <strong>
                        <?= $completed_tasks ?>
                    </strong>

                </div>


            </div>



            <!-- =================================================
                 TASK TABLE
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            All Tasks
                        </h2>

                        <p>
                            <?= $total_tasks ?>
                            task(s) in the system
                        </p>

                    </div>


                </div>



                <?php if ($total_tasks > 0): ?>


                    <div class="table-container">


                        <table class="projects-table">


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
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $tasks as $task
                                ): ?>


                                    <?php

                                    $is_overdue = false;

                                    if (
                                        !empty(
                                            $task["due_date"]
                                        ) &&
                                        $task["status"] !== "completed" &&
                                        $task["due_date"] < date("Y-m-d")
                                    ) {

                                        $is_overdue = true;

                                    }

                                    ?>


                                    <tr>


                                        <!-- TASK -->

                                        <td>


                                            <div
                                                class="task-title-cell"
                                            >

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

                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $task["description"]
                                                        ) ?>

                                                    </span>

                                                <?php endif; ?>


                                            </div>


                                        </td>



                                        <!-- PROJECT -->

                                        <td>

                                            <span
                                                class="task-project"
                                            >

                                                <?= htmlspecialchars(
                                                    $task["project_name"]
                                                ) ?>

                                            </span>

                                        </td>



                                        <!-- ASSIGNED -->

                                        <td>


                                            <?php if (
                                                !empty(
                                                    $task["assigned_name"]
                                                )
                                            ): ?>


                                                <div
                                                    class="task-assignee"
                                                >


                                                    <div
                                                        class="task-avatar"
                                                    >

                                                        <?= htmlspecialchars(
                                                            strtoupper(
                                                                substr(
                                                                    $task["assigned_name"],
                                                                    0,
                                                                    2
                                                                )
                                                            )
                                                        ) ?>

                                                    </div>


                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $task["assigned_name"]
                                                        ) ?>

                                                    </span>


                                                </div>


                                            <?php else: ?>

                                                <span>
                                                    Unassigned
                                                </span>

                                            <?php endif; ?>


                                        </td>



                                        <!-- DUE DATE -->

                                        <td>


                                            <?php if (
                                                !empty(
                                                    $task["due_date"]
                                                )
                                            ): ?>


                                                <span
                                                    class="task-due <?= $is_overdue ? "overdue" : "" ?>"
                                                >

                                                    <?= htmlspecialchars(
                                                        $task["due_date"]
                                                    ) ?>


                                                    <?php if (
                                                        $is_overdue
                                                    ): ?>

                                                        <br>

                                                        <small>
                                                            Overdue
                                                        </small>

                                                    <?php endif; ?>


                                                </span>


                                            <?php else: ?>

                                                —

                                            <?php endif; ?>


                                        </td>



                                        <!-- PRIORITY -->

                                        <td>


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


                                        </td>



                                        <!-- STATUS -->

                                        <td>


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


                                        </td>



                                        <!-- ACTIONS -->

                                        <td>


                                            <div
                                                class="task-actions"
                                            >


                                                <a
                                                    href="edit-task.php?id=<?= (int) $task["id"] ?>"
                                                    class="task-action-button"
                                                >
                                                    Edit
                                                </a>


                                                <form method="POST" action="delete-task.php" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= (int) $task["id"] ?>">
                                                    <button type="submit" class="task-action-button delete-task" onclick="return confirm('Are you sure you want to delete this task?');" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font:inherit;text-decoration:none;">
                                                        Delete
                                                    </button>
                                                </form>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-task">


                        <div class="empty-task-icon">
                            ✓
                        </div>


                        <h3>
                            No tasks yet
                        </h3>


                        <p>
                            Create your first task to get started.
                        </p>


                        <button
                            type="button"
                            class="primary-button"
                            onclick="openTaskModal()"
                        >
                            + Create Task
                        </button>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<!-- =====================================================
     CREATE TASK MODAL
====================================================== -->

<div
    class="modal-overlay"
    id="taskModal"
>


    <div class="modal">


        <div class="modal-header">


            <div>

                <h2>
                    Create New Task
                </h2>

                <p>
                    Create and assign a task to a project member.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="closeTaskModal()"
            >
                ×
            </button>


        </div>



        <?php if (count($projects) === 0): ?>


            <div class="empty-state">


                <div class="empty-icon">
                    ▣
                </div>


                <h3>
                    No projects available
                </h3>


                <p>
                    You must create a project before creating a task.
                </p>


                <a
                    href="projects.php"
                    class="primary-button"
                >
                    Create Project
                </a>


            </div>


        <?php else: ?>


            <form
                action="create-task.php"
                method="POST"
            >


                <!-- TITLE -->

                <div class="form-group">


                    <label>
                        Task Title
                    </label>


                    <input
                        type="text"
                        name="title"
                        placeholder="e.g. Design login page"
                        required
                    >


                </div>



                <!-- DESCRIPTION -->

                <div class="form-group">


                    <label>
                        Description
                    </label>


                    <textarea
                        name="description"
                        rows="4"
                        placeholder="Describe the task..."
                    ></textarea>


                </div>



                <!-- PROJECT -->

                <div class="form-group">


                    <label>
                        Project
                    </label>


                    <select
                        name="project_id"
                        required
                    >


                        <option value="">
                            Select Project
                        </option>


                        <?php foreach (
                            $projects as $project
                        ): ?>


                            <option
                                value="<?= (int) $project["id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $project["name"]
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>



                <!-- ASSIGNEE -->

                <div class="form-group">


                    <label>
                        Assign To
                    </label>


                    <select
                        name="assigned_to"
                    >


                        <option value="">
                            Unassigned
                        </option>


                        <?php foreach (
                            $users as $user
                        ): ?>


                            <option
                                value="<?= (int) $user["id"] ?>"
                            >

                                <?= htmlspecialchars(
                                    $user["full_name"]
                                ) ?>

                                —

                                <?= $user["role"] === "project_manager"
                                    ? "Project Manager"
                                    : "Team Member"
                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>



                <!-- DATES -->

                <div class="form-row">


                    <div class="form-group">


                        <label>
                            Due Date
                        </label>


                        <input
                            type="date"
                            name="due_date"
                        >


                    </div>


                    <div class="form-group">


                        <label>
                            Priority
                        </label>


                        <select
                            name="priority"
                        >


                            <option value="low">
                                Low
                            </option>


                            <option
                                value="medium"
                                selected
                            >
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



                <!-- STATUS -->

                <div class="form-group">


                    <label>
                        Status
                    </label>


                    <select
                        name="status"
                    >


                        <option
                            value="to_do"
                            selected
                        >
                            To Do
                        </option>


                        <option value="in_progress">
                            In Progress
                        </option>


                        <option value="review">
                            Review
                        </option>


                        <option value="completed">
                            Completed
                        </option>


                    </select>


                </div>



                <!-- HOURS -->

                <div class="form-row">


                    <div class="form-group">


                        <label>
                            Estimated Hours
                        </label>


                        <input
                            type="number"
                            name="estimated_hours"
                            min="0"
                            step="0.5"
                            value="0"
                        >


                    </div>


                    <div class="form-group">


                        <label>
                            Actual Hours
                        </label>


                        <input
                            type="number"
                            name="actual_hours"
                            min="0"
                            step="0.5"
                            value="0"
                        >


                    </div>


                </div>



                <!-- BUTTONS -->

                <div class="modal-actions">


                    <button
                        type="button"
                        class="secondary-button"
                        onclick="closeTaskModal()"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Create Task
                    </button>


                </div>


            </form>


        <?php endif; ?>


    </div>


</div>



<script>

/*
|--------------------------------------------------------------------------
| OPEN TASK MODAL
|--------------------------------------------------------------------------
*/

function openTaskModal() {

    const modal =
        document.getElementById("taskModal");

    modal.classList.add("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE TASK MODAL
|--------------------------------------------------------------------------
*/

function closeTaskModal() {

    const modal =
        document.getElementById("taskModal");

    modal.classList.remove("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "click",
    function(event) {

        const modal =
            document.getElementById("taskModal");

        if (event.target === modal) {

            closeTaskModal();

        }

    }
);

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