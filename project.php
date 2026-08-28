<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| CHECK LOGIN|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


$role = $_SESSION["role"] ?? "";


/*|--------------------------------------------------------------------------| GET PROJECT|--------------------------------------------------------------------------|*/

$project_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($project_id <= 0) {
    header("Location: " . ($role === "admin" ? "projects.php" : "manager-projects.php"));
    exit;
}


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
    INNER JOIN users u ON p.manager_id = u.id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $project = mysqli_fetch_assoc($result);
    }

    mysqli_stmt_close($stmt);
}


/*|--------------------------------------------------------------------------| PROJECT NOT FOUND|--------------------------------------------------------------------------|*/

if (!$project) {
    header("Location: " . ($role === "admin" ? "projects.php" : "manager-projects.php"));
    exit;
}


/*|--------------------------------------------------------------------------| GET TEAM MEMBERS|--------------------------------------------------------------------------|*/

$members = [];

$query = "
    SELECT
        u.id,
        u.full_name,
        u.email,
        u.status,
        pm.joined_at
    FROM project_members pm
    INNER JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
    ORDER BY u.full_name ASC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $members[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*|--------------------------------------------------------------------------| TASK SUMMARY|--------------------------------------------------------------------------|*/

$total_tasks = 0;
$todo_tasks = 0;
$in_progress_tasks = 0;
$review_tasks = 0;
$completed_tasks = 0;

$query = "SELECT COUNT(*) AS total FROM tasks WHERE project_id = ?";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_tasks = (int) $row["total"];
    }
    mysqli_stmt_close($stmt);
}

$query = "SELECT status, COUNT(*) AS total FROM tasks WHERE project_id = ? GROUP BY status";

$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            switch ($row["status"]) {
                case "to_do":       $todo_tasks = (int) $row["total"]; break;
                case "in_progress": $in_progress_tasks = (int) $row["total"]; break;
                case "review":      $review_tasks = (int) $row["total"]; break;
                case "completed":   $completed_tasks = (int) $row["total"]; break;
            }
        }
    }
    mysqli_stmt_close($stmt);
}


/*|--------------------------------------------------------------------------| PROGRESS|--------------------------------------------------------------------------|*/

$progress = 0;

if ($total_tasks > 0) {
    $progress = round(($completed_tasks / $total_tasks) * 100);
}


/*|--------------------------------------------------------------------------| FORMAT STATUS|--------------------------------------------------------------------------|*/

$project_status = ucfirst(str_replace("_", " ", $project["status"]));
$project_priority = ucfirst($project["priority"]);


/*|--------------------------------------------------------------------------| USER NAME|--------------------------------------------------------------------------|*/

$user_name = $_SESSION["full_name"] ?? "User";

if ($role === "admin") {
    $page_label = "ADMINISTRATION";
    $role_label = "Administrator";
    $home_link = "admin-dashboard.php";
} elseif ($role === "project_manager") {
    $page_label = "PROJECT MANAGER";
    $role_label = "Project Manager";
    $home_link = "manager-dashboard.php";
} else {
    $page_label = "TEAM MEMBER";
    $role_label = "Team Member";
    $home_link = "member-dashboard.php";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project["name"]) ?> | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .project-details-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 25px; }
        .project-details-title { display: flex; align-items: center; gap: 16px; }
        .project-details-avatar { width: 58px; height: 58px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; background: #eef2ff; color: #4f46e5; }
        .project-details-title h1 { margin: 0 0 6px; }
        .project-details-title p { margin: 0; }
        .project-details-actions { display: flex; gap: 10px; }
        .details-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .project-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .info-item { padding: 14px; border-radius: 10px; background: #f8f9fb; }
        .info-item span { display: block; font-size: 13px; margin-bottom: 5px; color: #777; }
        .info-item strong { display: block; font-size: 15px; }
        .project-description { line-height: 1.7; color: #555; margin-top: 15px; }
        .progress-section { margin-top: 25px; }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .task-summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 15px; }
        .task-summary-item { padding: 15px; border-radius: 10px; background: #f8f9fb; }
        .task-summary-item span { display: block; color: #777; font-size: 13px; margin-bottom: 5px; }
        .task-summary-item strong { font-size: 22px; }
        .member-list { display: flex; flex-direction: column; gap: 12px; }
        .member-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; background: #f8f9fb; }
        .member-avatar { width: 42px; height: 42px; border-radius: 50%; background: #e9edff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .member-details { flex: 1; }
        .member-details strong { display: block; }
        .member-details small { color: #777; }
        .empty-small { padding: 25px 10px; text-align: center; color: #777; }
        .status-priority-row { display: flex; gap: 10px; margin-top: 12px; }
        @media (max-width: 900px) { .details-grid { grid-template-columns: 1fr; } .project-details-header { flex-direction: column; } }
        @media (max-width: 600px) { .project-info-grid { grid-template-columns: 1fr; } .task-summary-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="<?= $home_link ?>" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <p class="nav-title">ACCOUNT</p>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-item"><span>↪</span> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
                <div class="search-box"><span>⌕</span><input type="text" placeholder="Search..."></div>
            </div>
            <div class="topbar-right">
                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($user_name, 0, 2))) ?></div>
                    <div class="profile-info"><strong><?= htmlspecialchars($user_name) ?></strong><span><?= htmlspecialchars($role_label) ?></span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="project-details-header">
                <div class="project-details-title">
                    <div class="project-details-avatar"><?= htmlspecialchars(strtoupper(substr($project["name"], 0, 1))) ?></div>
                    <div>
                        <span class="page-label"><?= htmlspecialchars($page_label) ?></span>
                        <h1><?= htmlspecialchars($project["name"]) ?></h1>
                        <p>Project details and progress</p>
                        <div class="status-priority-row">
                            <span class="status-badge status-<?= htmlspecialchars($project["status"]) ?>"><?= htmlspecialchars($project_status) ?></span>
                            <span class="priority-badge priority-<?= htmlspecialchars($project["priority"]) ?>"><?= htmlspecialchars($project_priority) ?></span>
                        </div>
                    </div>
                </div>
                <div class="project-details-actions">
                    <a href="<?= ($role === "admin" ? "projects.php" : "manager-projects.php") ?>" class="secondary-button">← Back</a>
                </div>
            </div>

            <div class="details-grid">
                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Project Information</h2><p>Basic information about this project</p></div></div>
                    <div class="project-info-grid">
                        <div class="info-item"><span>Project Manager</span><strong><?= htmlspecialchars($project["manager_name"]) ?></strong></div>
                        <div class="info-item"><span>Manager Email</span><strong><?= htmlspecialchars($project["manager_email"]) ?></strong></div>
                        <div class="info-item"><span>Start Date</span><strong><?= date("M d, Y", strtotime($project["start_date"])) ?></strong></div>
                        <div class="info-item"><span>Deadline</span><strong><?= !empty($project["end_date"]) ? date("M d, Y", strtotime($project["end_date"])) : "No deadline" ?></strong></div>
                        <div class="info-item"><span>Created</span><strong><?= date("M d, Y", strtotime($project["created_at"])) ?></strong></div>
                        <div class="info-item"><span>Last Updated</span><strong><?= date("M d, Y", strtotime($project["updated_at"])) ?></strong></div>
                    </div>
                    <div class="project-description">
                        <strong>Description</strong>
                        <p><?= nl2br(htmlspecialchars($project["description"] ?: "No description provided.")) ?></p>
                    </div>
                    <div class="progress-section">
                        <div class="progress-header"><strong>Project Progress</strong><strong><?= $progress ?>%</strong></div>
                        <div class="progress-bar"><div class="progress-fill" style="width: <?= $progress ?>%;"></div></div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-header"><div><h2>Task Summary</h2><p>Current project tasks</p></div></div>
                    <div class="task-summary-grid">
                        <div class="task-summary-item"><span>To Do</span><strong><?= $todo_tasks ?></strong></div>
                        <div class="task-summary-item"><span>In Progress</span><strong><?= $in_progress_tasks ?></strong></div>
                        <div class="task-summary-item"><span>Review</span><strong><?= $review_tasks ?></strong></div>
                        <div class="task-summary-item"><span>Completed</span><strong><?= $completed_tasks ?></strong></div>
                    </div>
                    <div class="progress-section">
                        <div class="progress-header"><span>Total Tasks</span><strong><?= $total_tasks ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div><h2>Team Members</h2><p>Members assigned to this project</p></div>
                    <strong><?= count($members) ?> member<?= count($members) !== 1 ? "s" : "" ?></strong>
                </div>
                <?php if (!empty($members)): ?>
                    <div class="member-list">
                        <?php foreach ($members as $member): ?>
                            <div class="member-item">
                                <div class="member-avatar"><?= htmlspecialchars(strtoupper(substr($member["full_name"], 0, 2))) ?></div>
                                <div class="member-details">
                                    <strong><?= htmlspecialchars($member["full_name"]) ?></strong>
                                    <small><?= htmlspecialchars($member["email"]) ?></small>
                                </div>
                                <span class="status-badge"><?= htmlspecialchars(ucfirst($member["status"])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-small">No team members assigned to this project yet.</div>
                <?php endif; ?>
            </div>

        </section>
    </main>

</div>

</body>
</html>
