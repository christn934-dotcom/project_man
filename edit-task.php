<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| CHECK LOGIN|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "send_email_notification.php";


/*|--------------------------------------------------------------------------| CHECK ROLE|--------------------------------------------------------------------------|*/

$role = $_SESSION["role"] ?? "";

if ($role !== "admin" && $role !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];


/*|--------------------------------------------------------------------------| GET TASK ID|--------------------------------------------------------------------------|*/

$task_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($task_id <= 0) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| GET TASK|--------------------------------------------------------------------------|*/

$task = null;

$query = "SELECT t.*,
    p.name AS project_name,
    p.manager_id,
    u.full_name AS assigned_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.id = ?
    LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $task_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$task = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);


/*|--------------------------------------------------------------------------| TASK NOT FOUND|--------------------------------------------------------------------------|*/

if (!$task) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| AUTHORIZATION|--------------------------------------------------------------------------|
| Admin can edit any task. Manager can only edit tasks on their own projects.
|--------------------------------------------------------------------------|*/

if ($role !== "admin" && (int) $task["manager_id"] !== $user_id) {
    header("Location: tasks.php");
    exit;
}


/*|--------------------------------------------------------------------------| GET PROJECTS|--------------------------------------------------------------------------|*/

$projects = [];

if ($role === "admin") {
    $query = "SELECT id, name FROM projects ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
} else {
    $query = "SELECT id, name FROM projects WHERE manager_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
}


/*|--------------------------------------------------------------------------| GET MEMBERS|--------------------------------------------------------------------------|*/

$members = [];

$query = "SELECT id, full_name, email FROM users WHERE role = 'member' AND status = 'active' ORDER BY full_name ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }
}


/*|--------------------------------------------------------------------------| UPDATE TASK|--------------------------------------------------------------------------|*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $project_id = (int) ($_POST["project_id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $assigned_to = !empty($_POST["assigned_to"]) ? (int) $_POST["assigned_to"] : null;
    $due_date = !empty($_POST["due_date"]) ? $_POST["due_date"] : null;
    $priority = $_POST["priority"] ?? "medium";
    $status = $_POST["status"] ?? "to_do";


    /* Validation */

    if ($project_id <= 0) {
        $error = "Please select a project.";
    } elseif ($title === "") {
        $error = "Task title is required.";
    } elseif (!in_array($priority, ["low", "medium", "high", "urgent"], true)) {
        $error = "Invalid priority.";
    } elseif (!in_array($status, ["to_do", "in_progress", "review", "completed"], true)) {
        $error = "Invalid status.";
    }


    if ($error === "") {

        if ($assigned_to === null) {

            $query = "UPDATE tasks SET project_id = ?, title = ?, description = ?, assigned_to = NULL, due_date = NULLIF(?, ''), priority = ?, status = ? WHERE id = ?";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isssssi", $project_id, $title, $description, $due_date, $priority, $status, $task_id);

        } else {

            $query = "UPDATE tasks SET project_id = ?, title = ?, description = ?, assigned_to = ?, due_date = NULLIF(?, ''), priority = ?, status = ? WHERE id = ?";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isiisssi", $project_id, $title, $description, $assigned_to, $due_date, $priority, $status, $task_id);

        }

        if (mysqli_stmt_execute($stmt)) {

            /* Activity log */
            $log_desc = "Updated task: " . $title;
            $log_action = "task_updated";
            $actor_id = (int) $_SESSION["user_id"];
            $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
            if ($lstmt) {
                mysqli_stmt_bind_param($lstmt, "iiss", $actor_id, $project_id, $log_action, $log_desc);
                mysqli_stmt_execute($lstmt);
                mysqli_stmt_close($lstmt);
            }

            /* Email notification */
            send_notification_email(
                $conn,
                $log_action,
                $log_desc,
                $project_id,
                $actor_id,
                $task_id
            );

            $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
            header("Location: $redirect?updated=1");
            exit;

        } else {

            $error = "Failed to update task.";
        }

        mysqli_stmt_close($stmt);

    }

}


$user_name = $_SESSION["full_name"] ?? ($role === "admin" ? "Administrator" : "Project Manager");
$role_label = $role === "admin" ? "Administrator" : "Project Manager";
$is_admin = ($role === "admin");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
</script>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <?php if ($is_admin): ?>
                <a href="admin-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="admin-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> Projects
                </a>
                <a href="tasks.php" class="nav-item active">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">MANAGEMENT</p>
                <a href="admin-users.php" class="nav-item">
                    <span class="nav-icon">♙</span> Users
                </a>
                <a href="admin-reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>
            <?php else: ?>
                <a href="manager-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="manager-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> My Projects
                </a>
                <a href="manager-tasks.php" class="nav-item active">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">WORKSPACE</p>
                <a href="manager-team.php" class="nav-item">
                    <span class="nav-icon">♙</span> Team
                </a>
                <a href="manager-reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>
            <?php endif; ?>
            <p class="nav-title">ACCOUNT</p>
            <a href="profile.php" class="nav-item">
                <span class="nav-icon">◉</span> My Profile
            </a>
        </nav>
        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span>
                <span>Dark Mode</span>
                <span class="toggle-track"></span>
            </button>
            <a href="logout.php" class="logout-item">
                <span>↪</span> Logout
            </a>
        </div>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
                <div class="search-box">
                    <span>⌕</span>
                    <input type="text" placeholder="Search...">
                </div>
            </div>
            <div class="topbar-right">
                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($user_name, 0, 2))) ?></div>
                    <div class="profile-info">
                        <strong><?= htmlspecialchars($user_name) ?></strong>
                        <span><?= htmlspecialchars($role_label) ?></span>
                    </div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>


        <!-- PAGE -->
        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">TASK MANAGEMENT</span>
                    <h1>Edit Task</h1>
                    <p>Update task details and assignment.</p>
                </div>
            </div>


            <?php if ($error !== ""): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>


            <div class="dashboard-card">

                <div class="card-header">
                    <div>
                        <h2>Task Information</h2>
                        <p>Editing: <strong><?= htmlspecialchars($task["title"]) ?></strong></p>
                    </div>
                </div>

                <form method="POST" action="">

                    <!-- PROJECT -->
                    <div class="form-group">
                        <label>Project</label>
                        <select name="project_id" required>
                            <option value="">Select Project</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?= (int)$project["id"] ?>" <?= (((int)$_POST["project_id"] ?? (int)$task["project_id"]) == (int)$project["id"] ? "selected" : "") ?>>
                                    <?= htmlspecialchars($project["name"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- TITLE -->
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($_POST["title"] ?? $task["title"]) ?>" required>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?= htmlspecialchars($_POST["description"] ?? $task["description"]) ?></textarea>
                    </div>

                    <!-- ASSIGN -->
                    <div class="form-group">
                        <label>Assign To</label>
                        <select name="assigned_to">
                            <option value="">Unassigned</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= (int)$member["id"] ?>" <?= ((int)($_POST["assigned_to"] ?? $task["assigned_to"])) == (int)$member["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($member["full_name"]) ?> — <?= htmlspecialchars($member["email"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- DATE + PRIORITY -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="date" name="due_date" value="<?= htmlspecialchars($_POST["due_date"] ?? $task["due_date"]) ?>">
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority">
                                <?php foreach (["low" => "Low", "medium" => "Medium", "high" => "High", "urgent" => "Urgent"] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= (($_POST["priority"] ?? $task["priority"]) === $val ? "selected" : "") ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="to_do" <?= (($_POST["status"] ?? $task["status"]) === "to_do" ? "selected" : "") ?>>To Do</option>
                            <option value="in_progress" <?= (($_POST["status"] ?? $task["status"]) === "in_progress" ? "selected" : "") ?>>In Progress</option>
                            <option value="review" <?= (($_POST["status"] ?? $task["status"]) === "review" ? "selected" : "") ?>>Review</option>
                            <option value="completed" <?= (($_POST["status"] ?? $task["status"]) === "completed" ? "selected" : "") ?>>Completed</option>
                        </select>
                    </div>

                    <!-- BUTTONS -->
                    <div class="modal-actions">
                        <a href="<?= $is_admin ? 'tasks.php' : 'manager-tasks.php' ?>" class="secondary-button">Cancel</a>
                        <button type="submit" class="primary-button">Save Changes</button>
                    </div>

                </form>

            </div>

        </section>

    </main>

</div><?php include "cookie_consent.php"; ?>
<script src="dark_mode.php"></script>
<script src="assets/js/responsive.js"></script>
</body>

</html>
