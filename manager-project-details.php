<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "avatar_helper.php";;


/*
|--------------------------------------------------------------------------
| ROLE CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}


$manager_id = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| GET PROJECT ID
|--------------------------------------------------------------------------
*/

$project_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($project_id <= 0) {
    header("Location: manager-projects.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET PROJECT
| Only allow the logged-in manager to view their own project.
|--------------------------------------------------------------------------
*/

$project = null;

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
        p.updated_at,
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

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $project_id,
        $manager_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $project = mysqli_fetch_assoc($result);
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| PROJECT NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$project) {
    header("Location: manager-projects.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET TEAM MEMBERS
|--------------------------------------------------------------------------
*/

$members = [];

$query = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.profile_image,
        u.status,
        pm.joined_at
    FROM project_members pm
    INNER JOIN users u
        ON pm.user_id = u.id
    WHERE pm.project_id = ?
    ORDER BY u.full_name ASC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $project_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $members[] = $row;
        }

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| GET TASK SUMMARY
|--------------------------------------------------------------------------
*/

$total_tasks = 0;
$todo_tasks = 0;
$in_progress_tasks = 0;
$review_tasks = 0;
$completed_tasks = 0;


/* Total tasks */

$query = "
    SELECT COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $project_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        $total_tasks = (int) $row["total"];

    }

    mysqli_stmt_close($stmt);
}


/* Task status counts */

$query = "
    SELECT
        status,
        COUNT(*) AS total
    FROM tasks
    WHERE project_id = ?
    GROUP BY status
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $project_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            switch ($row["status"]) {

                case "to_do":
                    $todo_tasks = (int) $row["total"];
                    break;

                case "in_progress":
                    $in_progress_tasks = (int) $row["total"];
                    break;

                case "review":
                    $review_tasks = (int) $row["total"];
                    break;

                case "completed":
                    $completed_tasks = (int) $row["total"];
                    break;
            }
        }
    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| PROJECT PROGRESS
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
| FORMAT STATUS
|--------------------------------------------------------------------------
*/

$project_status = ucfirst(
    str_replace(
        "_",
        " ",
        $project["status"]
    )
);

$project_priority = ucfirst(
    $project["priority"]
);


/*
|--------------------------------------------------------------------------
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$manager_name = $_SESSION["full_name"] ?? "Project Manager";

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
        <?= htmlspecialchars($project["name"]) ?> | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .project-details-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .project-details-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .project-details-avatar {
            width: 58px;
            height: 58px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 700;
            background: #eef2ff;
            color: #4f46e5;
        }

        .project-details-title h1 {
            margin: 0 0 6px;
        }

        .project-details-title p {
            margin: 0;
        }

        .project-details-actions {
            display: flex;
            gap: 10px;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            cursor: pointer;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .project-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            padding: 14px;
            border-radius: 10px;
            background: #f8f9fb;
        }

        .info-item span {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
            color: #777;
        }

        .info-item strong {
            display: block;
            font-size: 15px;
        }

        .project-description {
            line-height: 1.7;
            color: #555;
            margin-top: 15px;
        }

        .progress-section {
            margin-top: 25px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #4f46e5;
            border-radius: 20px;
        }

        .task-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .task-summary-item {
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fb;
        }

        .task-summary-item span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .task-summary-item strong {
            font-size: 22px;
        }

        .member-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .member-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            background: #f8f9fb;
        }

        .member-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e9edff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .member-details {
            flex: 1;
        }

        .member-details strong {
            display: block;
        }

        .member-details small {
            color: #777;
        }

        .empty-small {
            padding: 25px 10px;
            text-align: center;
            color: #777;
        }

        .status-priority-row {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        @media (max-width: 900px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

            .project-details-header {
                flex-direction: column;
            }

        }

        @media (max-width: 600px) {

            .project-info-grid {
                grid-template-columns: 1fr;
            }

            .task-summary-grid {
                grid-template-columns: 1fr;
            }

        }

        .status-workflow { display: flex; align-items: center; gap: 0; margin-top: 20px; padding: 18px; background: #f8f9fb; border-radius: 10px; }
        .workflow-step { display: flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #9ca3af; transition: all 0.3s; }
        .workflow-step.active { background: #4f46e5; color: #fff; box-shadow: 0 2px 10px rgba(79,70,229,0.3); }
        .workflow-step.done { background: #ecfdf5; color: #059669; }
        .workflow-step.hold { background: #fff7ed; color: #ea580c; }
        .workflow-step.cancelled { background: #fef2f2; color: #dc2626; }
        .workflow-step.pending { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .workflow-arrow { color: #d1d5db; font-size: 16px; margin: 0 4px; }
        .workflow-arrow.done { color: #059669; }
        body.dark .status-workflow { background: #1a1d24; }
        body.dark .workflow-step { color: #6b7280; }
        body.dark .workflow-arrow { color: #374151; }

        /* Dark mode text overrides */
        body.dark .project-details-label { color: #94a3b8; }
        body.dark .project-description { color: #cbd5e1; }
        body.dark .task-summary-item span { color: #94a3b8; }
        body.dark .member-details strong { color: #f1f5f9; }
        body.dark .member-details small { color: #94a3b8; }
        body.dark .no-members { color: #64748b; }
        body.dark .workflow-label { color: #94a3b8; }

    </style>

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



        <!-- =====================================================
             PAGE
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="project-details-header">


                <div class="project-details-title">


                    <div class="project-details-avatar">

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

                        <span class="page-label">
                            PROJECT
                        </span>


                        <h1>

                            <?= htmlspecialchars(
                                $project["name"]
                            ) ?>

                        </h1>


                        <p>
                            Project details and progress
                        </p>


                        <div class="status-priority-row">


                            <span
                                class="status-badge status-<?= htmlspecialchars(
                                    $project["status"]
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $project_status
                                ) ?>

                            </span>


                            <span
                                class="priority-badge priority-<?= htmlspecialchars(
                                    $project["priority"]
                                ) ?>"
                            >

                                <?= htmlspecialchars(
                                    $project_priority
                                ) ?>

                            </span>


                        </div>


                    </div>


                </div>


                <div class="project-details-actions">


                    <a
                        href="manager-projects.php"
                        class="secondary-button"
                    >
                        ← Back to Projects
                    </a>

                    <a
                        href="manager-edit-project.php?id=<?= (int) $project["id"] ?>"
                        class="primary-button"
                    >
                        ✎ Edit Project
                    </a>


                </div>


            </div>



            <!-- =================================================
                 PROJECT INFORMATION + TASK SUMMARY
            ================================================== -->

            <div class="details-grid">


                <!-- PROJECT INFORMATION -->

                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Project Information
                            </h2>

                            <p>
                                Basic information about this project
                            </p>

                        </div>

                    </div>


                    <div class="project-info-grid">


                        <div class="info-item">

                            <span>
                                Project Manager
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $project["manager_name"]
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                Manager Email
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $project["manager_email"]
                                ) ?>
                            </strong>

                        </div>


                        <div class="info-item">

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


                        <div class="info-item">

                            <span>
                                Deadline
                            </span>

                            <strong>

                                <?php if (!empty($project["end_date"])): ?>

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


                        <div class="info-item">

                            <span>
                                Created
                            </span>

                            <strong>

                                <?= date(
                                    "M d, Y",
                                    strtotime(
                                        $project["created_at"]
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <div class="info-item">

                            <span>
                                Last Updated
                            </span>

                            <strong>

                                <?= date(
                                    "M d, Y",
                                    strtotime(
                                        $project["updated_at"]
                                    )
                                ) ?>

                            </strong>

                        </div>


                    </div>


                    <div class="project-description">


                        <strong>
                            Description
                        </strong>


                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $project["description"]
                                    ?: "No description provided."
                                )
                            ) ?>

                        </p>


                    </div>


                    <!-- PROGRESS -->

                    <div class="progress-section">


                        <div class="progress-header">

                            <strong>
                                Project Progress
                            </strong>

                            <strong>
                                <?= $progress ?>%
                            </strong>

                        </div>


                        <div class="progress-bar">

                            <div
                                class="progress-fill"
                                style="width: <?= $progress ?>%;"
                            ></div>

                        </div>


                    </div>

                    <?php
                    $workflow = [["planning", "Planning"], ["in_progress", "In Progress"], ["pending_approval", "⏳ Approval"], ["completed", "Completed"]];
                    $statuses_order = ["planning" => 0, "in_progress" => 1, "pending_approval" => 2, "completed" => 3];
                    $current_idx = $statuses_order[$project["status"]] ?? -1;
                    $is_on_hold = ($project["status"] === "on_hold");
                    $is_cancelled = ($project["status"] === "cancelled");
                    ?>
                    <div class="status-workflow">
                        <?php foreach ($workflow as $i => [$val, $label]): ?>
                            <?php if ($i > 0): ?>
                                <span class="workflow-arrow <?= ($i <= $current_idx && !$is_cancelled) ? 'done' : '' ?>">→</span>
                            <?php endif; ?>
                            <div class="workflow-step <?=
                                $is_cancelled ? 'cancelled' :
                                ($val === $project['status'] ? 'active' : '') .
                                ($i < $current_idx && !$is_cancelled ? ' done' : '') .
                                ($is_on_hold && $val === 'in_progress' ? ' hold' : '')
                            ?>">
                                <?= ($i < $current_idx && !$is_cancelled && !$is_on_hold) ? '✓ ' : '' ?><?= $label ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($is_on_hold): ?>
                            <span class="workflow-arrow">→</span>
                            <div class="workflow-step hold">⏸ On Hold</div>
                        <?php elseif ($is_cancelled): ?>
                            <span class="workflow-arrow">→</span>
                            <div class="workflow-step cancelled">✕ Cancelled</div>
                        <?php endif; ?>
                    </div>

                </div>



                <!-- TASK SUMMARY -->

                <div class="dashboard-card">


                    <div class="card-header">

                        <div>

                            <h2>
                                Task Summary
                            </h2>

                            <p>
                                Current project tasks
                            </p>

                        </div>

                    </div>


                    <div class="task-summary-grid">


                        <div class="task-summary-item">

                            <span>
                                To Do
                            </span>

                            <strong>
                                <?= $todo_tasks ?>
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


                    <div class="progress-section">

                        <div class="progress-header">

                            <span>
                                Total Tasks
                            </span>

                            <strong>
                                <?= $total_tasks ?>
                            </strong>

                        </div>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 TEAM MEMBERS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Team Members
                        </h2>

                        <p>
                            Members assigned to this project
                        </p>

                    </div>


                    <strong>
                        <?= count($members) ?>
                        member<?= count($members) !== 1 ? "s" : "" ?>
                    </strong>


                </div>


                <?php if (!empty($members)): ?>


                    <div class="member-list">


                        <?php foreach ($members as $member): ?>


                            <div class="member-item">


                                <?= render_avatar($member["profile_image"] ?? null, $member["full_name"], (int)$member["id"], 'md') ?>

                                </div>


                                <div class="member-details">


                                    <strong>

                                        <?= htmlspecialchars(
                                            $member["full_name"]
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= htmlspecialchars(
                                            $member["email"]
                                        ) ?>

                                    </small>


                                </div>


                                <span
                                    class="status-badge"
                                >

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $member["status"]
                                        )
                                    ) ?>

                                </span>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="empty-small">

                        No team members have been assigned to this project yet.

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