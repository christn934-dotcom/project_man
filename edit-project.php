<?php

session_start();

require_once "config/database.php";
require_once "config/url.php";

/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "avatar_helper.php";;
require_once "send_email_notification.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


/*|--------------------------------------------------------------------------| GET: Show Edit Form|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $project_id = (int) ($_GET["id"] ?? 0);

    if ($project_id <= 0) {
        header("Location: projects.php");
        exit;
    }

    /* Fetch project */
    $stmt = mysqli_prepare($conn, "SELECT * FROM projects WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $project = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$project) {
        header("Location: projects.php");
        exit;
    }

    /* Fetch managers */
    $managers = [];
    $mgr_result = mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'project_manager' AND status = 'active' ORDER BY full_name ASC");
    if ($mgr_result) {
        while ($row = mysqli_fetch_assoc($mgr_result)) {
            $managers[] = $row;
        }
    }

    /* Fetch members */
    $members = [];
    $mem_result = mysqli_query($conn, "SELECT id, full_name, email, profile_image FROM users WHERE role = 'member' AND status = 'active' ORDER BY full_name ASC");
    if ($mem_result) {
        while ($row = mysqli_fetch_assoc($mem_result)) {
            $members[] = $row;
        }
    }

    /* Fetch assigned member IDs */
    $assigned_member_ids = [];
    $pm_result = mysqli_prepare($conn, "SELECT user_id FROM project_members WHERE project_id = ?");
    mysqli_stmt_bind_param($pm_result, "i", $project_id);
    mysqli_stmt_execute($pm_result);
    $pm_rows = mysqli_stmt_get_result($pm_result);
    if ($pm_rows) {
        while ($pm_row = mysqli_fetch_assoc($pm_rows)) {
            $assigned_member_ids[] = (int) $pm_row["user_id"];
        }
    }
    mysqli_stmt_close($pm_result);

    $error = $_GET["error"] ?? "";
    $admin_name = $_SESSION["full_name"] ?? "Administrator";
    $statuses = ["planning", "in_progress", "on_hold", "pending_approval", "completed", "cancelled"];
    $priorities = ["low", "medium", "high", "urgent"];

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>

<div class="admin-layout">
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="admin-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="projects.php" class="nav-item active"><span class="nav-icon">▣</span> Projects</a>
            <a href="tasks.php" class="nav-item"><span class="nav-icon">✓</span> Tasks</a>
            <p class="nav-title">MANAGEMENT</p>
            <a href="users.php" class="nav-item"><span class="nav-icon">♙</span> Users</a>
            <a href="notifications.php" class="nav-item"><span class="nav-icon">♧</span> Notifications</a>
            <p class="nav-title">SYSTEM</p>
            <a href="settings.php" class="nav-item"><span class="nav-icon">⚙</span> Settings</a>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span><span>Dark Mode</span><span class="toggle-track"></span>
            </button>
            <a href="logout.php" class="logout-item"><span>↪</span> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
            </div>
            <div class="topbar-right">
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <span class="theme-icon-light"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></span>
                    <span class="theme-icon-dark"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
                </button>
                <button class="notification-button" type="button" onclick="window.location.href='notifications.php'" style="position:relative;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><span class="notification-dot" id="notifBadge" style="display:none;"></span>
                </button>
                <div class="admin-profile">
                    <?= render_avatar($_SESSION["profile_image"] ?? null, $admin_name, (int)($_SESSION["user_id"])) ?>
                    <div class="profile-info"><strong><?= htmlspecialchars($admin_name) ?></strong><span>Administrator</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">
            <div class="page-header">
                <div>
                    <span class="page-label">PROJECT MANAGEMENT</span>
                    <h1>Edit Project</h1>
                    <p>Update project details, status, and team members.</p>
                </div>
                <div class="page-actions">
                    <a href="projects.php" class="secondary-button">← Back to Projects</a>
                </div>
            </div>

            <?php if ($error !== ""): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-container">
                <div class="dashboard-card">
                    <form method="POST" action="edit-project.php">
                        <input type="hidden" name="project_id" value="<?= (int) $project["id"] ?>">

                        <div class="form-group">
                            <label>Project Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($project["name"]) ?>" placeholder="Enter project name" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Describe the project..."><?= htmlspecialchars($project["description"]) ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" value="<?= htmlspecialchars($project["start_date"]) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" value="<?= htmlspecialchars($project["end_date"] ?? "") ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?= $s ?>" <?= ($project["status"] === $s) ? "selected" : "" ?>><?= ucfirst(str_replace("_", " ", $s)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority">
                                    <?php foreach ($priorities as $p): ?>
                                        <option value="<?= $p ?>" <?= ($project["priority"] === $p) ? "selected" : "" ?>><?= ucfirst($p) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Project Manager</label>
                            <select name="manager_id" required>
                                <option value="">Select Project Manager</option>
                                <?php foreach ($managers as $mgr): ?>
                                    <option value="<?= (int) $mgr["id"] ?>" <?= ((int) $project["manager_id"] === (int) $mgr["id"]) ? "selected" : "" ?>>
                                        <?= htmlspecialchars($mgr["full_name"]) ?> — <?= htmlspecialchars($mgr["email"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($managers)): ?>
                                <small style="color:#dc2626;">No active Project Managers found.</small>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Team Members</label>
                            <div class="member-selection">
                                <?php if (empty($members)): ?>
                                    <p class="no-members">No active team members available.</p>
                                <?php else: ?>
                                    <?php foreach ($members as $member): ?>
                                        <label class="member-option">
                                            <input type="checkbox" name="members[]" value="<?= (int) $member["id"] ?>" <?= in_array((int) $member["id"], $assigned_member_ids) ? "checked" : "" ?>>
                                            <?= render_avatar($member["profile_image"] ?? null, $member["full_name"], (int)$member["id"]) ?>
                                            <span class="member-details">
                                                <strong><?= htmlspecialchars($member["full_name"]) ?></strong>
                                                <small><?= htmlspecialchars($member["email"]) ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <a href="projects.php" class="secondary-button">Cancel</a>
                            <button type="submit" class="primary-button">Save Changes</button>
                        </div>
                    </form>
                </div>
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
                badge.textContent = data.count > 99 ? '99+' : data.count;
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
    <?php
    exit;
}


/*|--------------------------------------------------------------------------| POST: Process Update|--------------------------------------------------------------------------|*/

$project_id = (int) ($_POST["project_id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$description = trim($_POST["description"] ?? "");
$start_date = $_POST["start_date"] ?? "";
$end_date = $_POST["end_date"] ?? "";
$status = $_POST["status"] ?? "planning";
$priority = $_POST["priority"] ?? "medium";
$manager_id = (int) ($_POST["manager_id"] ?? 0);
$selected_members = $_POST["members"] ?? [];

$error = "";

if ($project_id <= 0) {
    $error = "Invalid project.";
} elseif ($name === "") {
    $error = "Project name is required.";
} elseif ($start_date === "") {
    $error = "Start date is required.";
} elseif ($manager_id <= 0) {
    $error = "Please select a Project Manager.";    } elseif (!in_array($status, ["planning", "in_progress", "on_hold", "pending_approval", "completed", "cancelled"], true)) {
        $error = "Invalid status.";
} elseif (!in_array($priority, ["low", "medium", "high", "urgent"], true)) {
    $error = "Invalid priority.";
} elseif ($end_date !== "" && $end_date < $start_date) {
    $error = "End date cannot be before start date.";
}

/* Validate status transition */
if ($error === "" && $project_id > 0) {
    $cur = mysqli_prepare($conn, "SELECT status FROM projects WHERE id = ?");
    mysqli_stmt_bind_param($cur, "i", $project_id);
    mysqli_stmt_execute($cur);
    $cur_result = mysqli_stmt_get_result($cur);
    $cur_row = mysqli_fetch_assoc($cur_result);
    mysqli_stmt_close($cur);
    $current_status = $cur_row["status"] ?? "";

    $valid_transitions = [
        "planning"        => ["in_progress", "on_hold", "cancelled"],
        "in_progress"     => ["on_hold", "pending_approval", "cancelled"],
        "on_hold"         => ["in_progress", "cancelled"],
        "pending_approval" => ["in_progress", "completed", "cancelled"],
        "completed"       => [],
        "cancelled"       => [],
    ];

    if ($current_status !== $status && !in_array($status, $valid_transitions[$current_status] ?? [], true)) {
        $error = "Cannot move project from '" . ucfirst(str_replace("_", " ", $current_status)) . "' to '" . ucfirst(str_replace("_", " ", $status)) . "'.";
    }
}

if ($error === "") {
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND role = 'project_manager' AND status = 'active' LIMIT 1");
    mysqli_stmt_bind_param($check, "i", $manager_id);
    mysqli_stmt_execute($check);
    $manager_result = mysqli_stmt_get_result($check);
    if (mysqli_num_rows($manager_result) === 0) {
        $error = "The selected Project Manager is invalid.";
    }
    mysqli_stmt_close($check);
}

if ($error === "") {

    mysqli_begin_transaction($conn);

    try {

        $update = mysqli_prepare($conn, "UPDATE projects SET name = ?, description = ?, start_date = ?, end_date = NULLIF(?, ''), status = ?, priority = ?, manager_id = ? WHERE id = ?");
        mysqli_stmt_bind_param($update, "ssssssii", $name, $description, $start_date, $end_date, $status, $priority, $manager_id, $project_id);

        if (!mysqli_stmt_execute($update)) {
            throw new Exception("Failed to update project.");
        }
        mysqli_stmt_close($update);

        /* Replace team members */
        $delete_members = mysqli_prepare($conn, "DELETE FROM project_members WHERE project_id = ?");
        mysqli_stmt_bind_param($delete_members, "i", $project_id);
        if (!mysqli_stmt_execute($delete_members)) {
            throw new Exception("Failed to update team members.");
        }
        mysqli_stmt_close($delete_members);

        if (!empty($selected_members)) {
            $add_member = mysqli_prepare($conn, "INSERT INTO project_members (project_id, user_id) VALUES (?, ?)");
            foreach ($selected_members as $member_id) {
                $member_id = (int) $member_id;
                mysqli_stmt_bind_param($add_member, "ii", $project_id, $member_id);
                if (!mysqli_stmt_execute($add_member)) {
                    throw new Exception("Failed to add team member.");
                }
            }
            mysqli_stmt_close($add_member);
        }

        /* Activity Log — special actions for approval/rejection */
        $admin_id = (int) $_SESSION["user_id"];

        if ($status === "completed" && $current_status === "pending_approval") {
            $action = "project_approved";
            $log_description = "Admin approved and completed project \"" . $name . "\"";
        } elseif ($current_status === "pending_approval" && $status === "in_progress") {
            $action = "project_rejected";
            $log_description = "Admin sent project \"" . $name . "\" back for revisions";
        } else {
            $action = "project_updated";
            $log_description = "Updated project: " . $name;
        }

        $activity = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($activity, "iiss", $admin_id, $project_id, $action, $log_description);
        if (!mysqli_stmt_execute($activity)) {
            throw new Exception("Failed to log activity.");
        }
        mysqli_stmt_close($activity);

        /* Email notification — notify manager + members */
        send_notification_email($conn, $action, $log_description, $project_id, $admin_id);

        mysqli_commit($conn);
        header("Location: projects.php?updated=1");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

if ($error !== "") {
    header("Location: edit-project.php?id=" . $project_id . "&error=" . urlencode($error));
    exit;
}
?>
