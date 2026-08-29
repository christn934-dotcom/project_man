<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| CHECK LOGIN|--------------------------------------------------------------------------|*/

require_once "auth_check.php";


$role = $_SESSION["role"] ?? "";
$user_name = $_SESSION["full_name"] ?? "User";

/* Mark notifications as read by updating last_seen_at */
$upd_stmt = mysqli_prepare($conn, "UPDATE users SET last_seen_at = NOW() WHERE id = ? AND (last_seen_at IS NULL OR last_seen_at < DATE_SUB(NOW(), INTERVAL 10 SECOND))");
if ($upd_stmt) {
    mysqli_stmt_bind_param($upd_stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($upd_stmt);
    mysqli_stmt_close($upd_stmt);
}


/*|--------------------------------------------------------------------------| GET ROLE-SPECIFIC SIDEBAR|--------------------------------------------------------------------------|*/

$sidebar_links = [];

if ($role === "admin") {
    $sidebar_links = [
        ["admin-dashboard.php", "▦", "Dashboard", false],
        ["admin-users.php",     "♙", "Users",     false],
        ["admin-projects.php",  "▣", "Projects",  false],
    ];
    $section_management = [
        ["admin-reports.php",  "▥", "Reports",       false],
        ["admin-activity.php", "◷", "Activity Logs", false],
        ["notifications.php",  "♧", "Notifications", false],
    ];
    $section_account = [
        ["profile.php", "◉", "My Profile", false],
    ];
    $role_label = "Administrator";

} elseif ($role === "project_manager") {
    $sidebar_links = [
        ["manager-dashboard.php",  "▦", "Dashboard",   false],
        ["manager-projects.php",   "▣", "My Projects", false],
        ["manager-tasks.php",      "✓", "Tasks",       false],
    ];
    $section_management = [
        ["manager-team.php",    "♙", "Team",          false],
        ["manager-reports.php", "▥", "Reports",       false],
        ["notifications.php",   "♧", "Notifications", false],
    ];
    $section_account = [
        ["profile.php", "◉", "My Profile", false],
    ];
    $role_label = "Project Manager";

} else {
    $sidebar_links = [
        ["member-dashboard.php", "▦", "Dashboard",   false],
        ["member-projects.php",  "▣", "My Projects", false],
        ["member-tasks.php",     "✓", "My Tasks",    false],
    ];
    $section_management = [
        ["team.php",            "♙", "Team",          false],
        ["notifications.php",   "♧", "Notifications", true],
    ];
    $active_page = "notifications.php";
    $section_account = [
        ["profile.php", "◉", "My Profile", false],
    ];
    $role_label = "Team Member";
}


/*|--------------------------------------------------------------------------| GET NOTIFICATIONS|--------------------------------------------------------------------------|
| For now, show recent activity logs related to the user's projects.
|--------------------------------------------------------------------------|*/

$notifications = [];

if ($role === "admin") {

    $query = "
        SELECT a.action, a.description, a.created_at,
               u.full_name, p.name AS project_name
        FROM activity_logs a
        INNER JOIN users u ON a.user_id = u.id
        LEFT JOIN projects p ON a.project_id = p.id
        ORDER BY a.created_at DESC
        LIMIT 20
    ";

    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }

} elseif ($role === "project_manager") {

    $manager_id = (int) $_SESSION["user_id"];

    $query = "
        SELECT a.action, a.description, a.created_at,
               u.full_name, p.name AS project_name
        FROM activity_logs a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN projects p ON a.project_id = p.id
        WHERE p.manager_id = ?
        ORDER BY a.created_at DESC
        LIMIT 20
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $manager_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
            }
        }

        mysqli_stmt_close($stmt);
    }

} else {

    $member_id = (int) $_SESSION["user_id"];

    $query = "
        SELECT a.action, a.description, a.created_at,
               u.full_name, p.name AS project_name
        FROM activity_logs a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN projects p ON a.project_id = p.id
        WHERE p.id IN (
            SELECT project_id FROM project_members WHERE user_id = ?
        )
        ORDER BY a.created_at DESC
        LIMIT 20
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $member_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = $row;
            }
        }

        mysqli_stmt_close($stmt);
    }

}


/*|--------------------------------------------------------------------------| ACTION ICON|--------------------------------------------------------------------------|*/

function notif_icon($action) {
    $icons = [
        "project_created" => "▣",
        "project_updated" => "✎",
        "project_deleted" => "✕",
        "task_created"    => "✓",
        "task_updated"    => "✎",
        "task_deleted"    => "✕",
        "user_created"    => "♙",
        "user_updated"    => "✎",
        "user_deleted"    => "✕",
    ];
    return $icons[$action] ?? "•";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .notif-list { display: flex; flex-direction: column; }
        .notif-item { display: flex; align-items: flex-start; gap: 14px; padding: 18px 0; border-bottom: 1px solid #f1f5f9; }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #eef2ff; color: #4f46e5; font-size: 16px; flex-shrink: 0; }
        .notif-content { flex: 1; }
        .notif-content strong { display: block; margin-bottom: 3px; font-size: 13px; }
        .notif-content p { color: #6b7280; font-size: 13px; margin: 0 0 4px; }
        .notif-content small { color: #9ca3af; font-size: 11px; }
        .notif-empty { text-align: center; padding: 50px 20px; color: #9ca3af; }
    </style>
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
</script>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <?php foreach ($sidebar_links as $link): ?>
                <a href="<?= $link[0] ?>" class="nav-item <?= $link[3] ? "active" : "" ?>">
                    <span class="nav-icon"><?= $link[1] ?></span> <?= $link[2] ?>
                </a>
            <?php endforeach; ?>
            <p class="nav-title">COLLABORATION</p>
            <?php foreach ($section_management as $link): ?>
                <a href="<?= $link[0] ?>" class="nav-item <?= $link[3] ? "active" : "" ?>">
                    <span class="nav-icon"><?= $link[1] ?></span> <?= $link[2] ?>
                </a>
            <?php endforeach; ?>
            <p class="nav-title">SYSTEM</p>
            <?php foreach ($section_account as $link): ?>
                <a href="<?= $link[0] ?>" class="nav-item <?= $link[3] ? "active" : "" ?>">
                    <span class="nav-icon"><?= $link[1] ?></span> <?= $link[2] ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="toggle-icon">🌙</span>
                <span>Dark Mode</span>
                <span class="toggle-track"></span>
            </button>
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
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($user_name, 0, 2))) ?></div>
                    <div class="profile-info"><strong><?= htmlspecialchars($user_name) ?></strong><span><?= htmlspecialchars($role_label) ?></span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">NOTIFICATIONS</span>
                    <h1>Notifications</h1>
                    <p>Recent activity across your projects.</p>
                </div>
            </div>

            <div class="dashboard-card">
                <?php if (!empty($notifications)): ?>
                    <div class="notif-list">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notif-item">
                                <div class="notif-icon"><?= notif_icon($notif["action"]) ?></div>
                                <div class="notif-content">
                                    <strong><?= htmlspecialchars($notif["full_name"]) ?></strong>
                                    <p><?= htmlspecialchars($notif["description"]) ?></p>
                                    <?php if (!empty($notif["project_name"])): ?>
                                        <small>Project: <?= htmlspecialchars($notif["project_name"]) ?></small><br>
                                    <?php endif; ?>
                                    <small><?= htmlspecialchars($notif["created_at"]) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="notif-empty">
                        <p>No notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </main>

</div><?php include "cookie_consent.php"; ?>
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
