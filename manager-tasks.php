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
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$status_filter = $_GET["status"] ?? "all";

$priority_filter = $_GET["priority"] ?? "all";

$search = trim($_GET["search"] ?? "");


/*
|--------------------------------------------------------------------------
| ALLOWED FILTER VALUES
|--------------------------------------------------------------------------
*/

$allowed_statuses = [
    "all",
    "to_do",
    "in_progress",
    "review",
    "completed"
];


$allowed_priorities = [
    "all",
    "low",
    "medium",
    "high",
    "urgent"
];


if (!in_array($status_filter, $allowed_statuses, true)) {

    $status_filter = "all";

}


if (!in_array($priority_filter, $allowed_priorities, true)) {

    $priority_filter = "all";

}


/*
|--------------------------------------------------------------------------
| TASK STATISTICS
|--------------------------------------------------------------------------
*/

$total_tasks = 0;

$pending_tasks = 0;

$in_progress_tasks = 0;

$review_tasks = 0;

$completed_tasks = 0;


/*
|--------------------------------------------------------------------------
| TOTAL TASKS
|--------------------------------------------------------------------------
*/

$query = "
    SELECT COUNT(*) AS total
    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE p.manager_id = ?
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

        $row = mysqli_fetch_assoc($result);

        $total_tasks = (int) $row["total"];

    }


    mysqli_stmt_close($stmt);

}


/*
|--------------------------------------------------------------------------
| PENDING TASKS
|--------------------------------------------------------------------------
*/

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

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

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
| IN PROGRESS TASKS
|--------------------------------------------------------------------------
*/

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

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);


    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $in_progress_tasks = (int) $row["total"];

    }


    mysqli_stmt_close($stmt);

}


/*
|--------------------------------------------------------------------------
| REVIEW TASKS
|--------------------------------------------------------------------------
*/

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

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

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
| COMPLETED TASKS
|--------------------------------------------------------------------------
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


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

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
| GET MANAGER TASKS
|--------------------------------------------------------------------------
*/

$tasks = [];


$query = "
    SELECT

        t.id,
        t.title,
        t.description,
        t.due_date,
        t.priority,
        t.status,
        t.estimated_hours,
        t.actual_hours,
        t.created_at,

        p.id AS project_id,
        p.name AS project_name,

        u.id AS assignee_id,
        u.full_name AS assignee_name,
        u.email AS assignee_email

    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    LEFT JOIN users u
        ON t.assigned_to = u.id

    WHERE p.manager_id = ?
";


$params = [$manager_id];

$types = "i";


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status_filter !== "all") {

    $query .= "
        AND t.status = ?
    ";

    $params[] = $status_filter;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| PRIORITY FILTER
|--------------------------------------------------------------------------
*/

if ($priority_filter !== "all") {

    $query .= "
        AND t.priority = ?
    ";

    $params[] = $priority_filter;

    $types .= "s";

}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $query .= "
        AND (
            t.title LIKE ?
            OR t.description LIKE ?
            OR p.name LIKE ?
            OR u.full_name LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "ssss";

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$query .= "
    ORDER BY
        CASE
            WHEN t.status = 'to_do' THEN 1
            WHEN t.status = 'in_progress' THEN 2
            WHEN t.status = 'review' THEN 3
            WHEN t.status = 'completed' THEN 4
            ELSE 5
        END,
        t.due_date ASC,
        t.created_at DESC
";


$stmt = mysqli_prepare($conn, $query);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
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
| FILTERED TASK COUNT
|--------------------------------------------------------------------------
*/

$filtered_task_count = count($tasks);

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
        | TASK PAGE EXTRA STYLES
        |--------------------------------------------------------------------------
        */

        .task-filter-bar {

            display: flex;

            gap: 12px;

            align-items: center;

            flex-wrap: wrap;

            margin-bottom: 24px;

        }


        .task-search-form {

            display: flex;

            gap: 10px;

            flex: 1;

            min-width: 240px;

        }


        .task-search-form input {

            width: 100%;

            padding: 11px 14px;

            border: 1px solid #ddd;

            border-radius: 8px;

            outline: none;

            font-size: 14px;

        }


        .task-filter {

            padding: 11px 14px;

            border: 1px solid #ddd;

            border-radius: 8px;

            background: #fff;

            font-size: 14px;

            cursor: pointer;

        }


        .task-summary {

            display: flex;

            gap: 20px;

            flex-wrap: wrap;

            margin-bottom: 24px;

        }


        .task-summary-item {

            background: #fff;

            border: 1px solid #eee;

            border-radius: 10px;

            padding: 14px 18px;

            min-width: 130px;

        }


        .task-summary-item span {

            display: block;

            font-size: 13px;

            margin-bottom: 5px;

            opacity: .7;

        }


        .task-summary-item strong {

            font-size: 24px;

        }


        .task-title-cell strong {

            display: block;

            margin-bottom: 4px;

        }


        .task-title-cell small {

            display: block;

            opacity: .65;

            max-width: 260px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .task-project-name {

            font-weight: 600;

        }


        .task-assignee {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .task-assignee-avatar {

            width: 34px;

            height: 34px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 700;

            background: #eee;

        }


        .task-assignee-info strong {

            display: block;

            font-size: 13px;

        }


        .task-assignee-info small {

            display: block;

            font-size: 11px;

            opacity: .65;

        }


        .task-action-button {

            border: none;

            background: transparent;

            cursor: pointer;

            font-size: 20px;

            padding: 5px 10px;

        }


        .task-empty {

            text-align: center;

            padding: 60px 20px;

        }


        .task-empty .empty-icon {

            font-size: 40px;

            margin-bottom: 10px;

        }


        .task-count {

            font-size: 14px;

            opacity: .7;

        }


        @media (max-width: 768px) {

            .task-filter-bar {

                flex-direction: column;

                align-items: stretch;

            }


            .task-search-form {

                width: 100%;

            }


            .task-summary {

                display: grid;

                grid-template-columns: repeat(2, 1fr);

            }

        }

    </style>

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
                        placeholder="Search tasks..."
                        id="quickTaskSearch"
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
             PAGE CONTENT
        ================================================== -->


        <section class="dashboard-content">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->


            <div class="page-header">


                <div>


                    <span class="page-label">
                        WORK MANAGEMENT
                    </span>


                    <h1>
                        Tasks
                    </h1>


                    <p>
                        Manage tasks across all your projects.
                    </p>


                </div>


                <div class="page-actions">


                    <a
                        href="manager-create-task.php"
                        class="primary-button"
                    >
                        + New Task
                    </a>


                </div>


            </div>



            <!-- =================================================
                 TASK SUMMARY
            ================================================== -->


            <div class="task-summary">


                <div class="task-summary-item">

                    <span>
                        Total Tasks
                    </span>

                    <strong>
                        <?= $total_tasks ?>
                    </strong>

                </div>


                <div class="task-summary-item">

                    <span>
                        To Do
                    </span>

                    <strong>
                        <?= $pending_tasks ?>
                    </strong>

                </div>


                <div class="task-summary-item">

                    <span>
                        In Progress
                    </span>

                    <strong>
                        <?= $in_progress_tasks ?>
                    </strong>

                </div>


                <div class="task-summary-item">

                    <span>
                        Review
                    </span>

                    <strong>
                        <?= $review_tasks ?>
                    </strong>

                </div>


                <div class="task-summary-item">

                    <span>
                        Completed
                    </span>

                    <strong>
                        <?= $completed_tasks ?>
                    </strong>

                </div>


            </div>



            <!-- =================================================
                 FILTERS
            ================================================== -->


            <div class="task-filter-bar">


                <form
                    method="GET"
                    action="manager-tasks.php"
                    class="task-search-form"
                    id="taskFilterForm"
                >


                    <input
                        type="text"
                        name="search"
                        id="taskSearch"
                        placeholder="Search task, project or assignee..."
                        value="<?= htmlspecialchars($search) ?>"
                    >


                    <select
                        name="status"
                        class="task-filter"
                    >

                        <option value="all"
                            <?= $status_filter === "all" ? "selected" : "" ?>>
                            All Statuses
                        </option>

                        <option value="to_do"
                            <?= $status_filter === "to_do" ? "selected" : "" ?>>
                            To Do
                        </option>

                        <option value="in_progress"
                            <?= $status_filter === "in_progress" ? "selected" : "" ?>>
                            In Progress
                        </option>

                        <option value="review"
                            <?= $status_filter === "review" ? "selected" : "" ?>>
                            Review
                        </option>

                        <option value="completed"
                            <?= $status_filter === "completed" ? "selected" : "" ?>>
                            Completed
                        </option>

                    </select>


                    <select
                        name="priority"
                        class="task-filter"
                    >

                        <option value="all"
                            <?= $priority_filter === "all" ? "selected" : "" ?>>
                            All Priorities
                        </option>

                        <option value="low"
                            <?= $priority_filter === "low" ? "selected" : "" ?>>
                            Low
                        </option>

                        <option value="medium"
                            <?= $priority_filter === "medium" ? "selected" : "" ?>>
                            Medium
                        </option>

                        <option value="high"
                            <?= $priority_filter === "high" ? "selected" : "" ?>>
                            High
                        </option>

                        <option value="urgent"
                            <?= $priority_filter === "urgent" ? "selected" : "" ?>>
                            Urgent
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="primary-button"
                    >
                        Filter
                    </button>


                    <?php if (
                        $search !== "" ||
                        $status_filter !== "all" ||
                        $priority_filter !== "all"
                    ): ?>

                        <a
                            href="manager-tasks.php"
                            class="secondary-button"
                        >
                            Clear
                        </a>

                    <?php endif; ?>


                </form>


            </div>



            <!-- =================================================
                 TASK TABLE
            ================================================== -->


            <div class="dashboard-card">


                <div class="card-header">


                    <div>


                        <h2>
                            Project Tasks
                        </h2>


                        <p class="task-count">

                            Showing
                            <?= $filtered_task_count ?>

                            of

                            <?= $total_tasks ?>

                            task(s)

                        </p>


                    </div>


                </div>



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


                                <?php foreach (
                                    $tasks
                                    as $task
                                ): ?>


                                    <tr
                                        class="task-row"
                                        data-search="<?= htmlspecialchars(
                                            strtolower(
                                                $task["title"]
                                                . " "
                                                . ($task["description"] ?? "")
                                                . " "
                                                . $task["project_name"]
                                                . " "
                                                . ($task["assignee_name"] ?? "")
                                            )
                                        ) ?>"
                                    >


                                        <!-- TASK -->


                                        <td>


                                            <div class="task-title-cell">


                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $task["title"]
                                                    ) ?>

                                                </strong>


                                                <small>

                                                    <?= htmlspecialchars(
                                                        $task["description"]
                                                        ?: "No description"
                                                    ) ?>

                                                </small>


                                            </div>


                                        </td>



                                        <!-- PROJECT -->


                                        <td>


                                            <span class="task-project-name">

                                                <?= htmlspecialchars(
                                                    $task["project_name"]
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- ASSIGNEE -->


                                        <td>


                                            <?php if (
                                                !empty(
                                                    $task["assignee_name"]
                                                )
                                            ): ?>


                                                <div class="task-assignee">


                                                    <div
                                                        class="task-assignee-avatar"
                                                    >

                                                        <?= htmlspecialchars(
                                                            strtoupper(
                                                                substr(
                                                                    $task["assignee_name"],
                                                                    0,
                                                                    2
                                                                )
                                                            )
                                                        ) ?>

                                                    </div>


                                                    <div class="task-assignee-info">


                                                        <strong>

                                                            <?= htmlspecialchars(
                                                                $task["assignee_name"]
                                                            ) ?>

                                                        </strong>


                                                        <small>

                                                            <?= htmlspecialchars(
                                                                $task["assignee_email"]
                                                            ) ?>

                                                        </small>


                                                    </div>


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


                                                <?= htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $task["due_date"]
                                                        )
                                                    )
                                                ) ?>


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

                                                <?= htmlspecialchars(
                                                    ucfirst(
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



                                        <!-- ACTION -->


                                        <td>


                                            <button
                                                type="button"
                                                class="task-action-button"
                                                onclick="viewTask(
                                                    <?= (int) $task["id"] ?>
                                                )"
                                            >

                                                ⋮

                                            </button>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="task-empty">


                        <div class="empty-icon">
                            ✓
                        </div>


                        <h3>
                            No Tasks Found
                        </h3>


                        <?php if (
                            $search !== "" ||
                            $status_filter !== "all" ||
                            $priority_filter !== "all"
                        ): ?>


                            <p>
                                No tasks match your current filters.
                            </p>


                            <a
                                href="manager-tasks.php"
                                class="secondary-button"
                            >
                                Clear Filters
                            </a>


                        <?php else: ?>


                            <p>
                                No tasks have been created for your projects yet.
                            </p>


                            <a
                                href="manager-create-task.php"
                                class="primary-button"
                            >
                                + Create Your First Task
                            </a>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<script>

/*
|--------------------------------------------------------------------------
| QUICK SEARCH
|--------------------------------------------------------------------------
|
| This searches the tasks already displayed on the page.
|
*/

const quickSearch =
    document.getElementById(
        "quickTaskSearch"
    );


if (quickSearch) {

    quickSearch.addEventListener(
        "input",
        function () {

            const value =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    ".task-row"
                );


            rows.forEach(
                function (row) {

                    const text =
                        row
                            .getAttribute(
                                "data-search"
                            )
                            .toLowerCase();


                    if (
                        text.includes(value)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| VIEW TASK
|--------------------------------------------------------------------------
*/

function viewTask(id) {

    window.location.href =
        "manager-task-details.php?id="
        + id;

}

</script>


</body>

</html>