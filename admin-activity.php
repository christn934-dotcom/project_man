<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "avatar_helper.php";;

/* Update last_seen_at for notification badge tracking */
$__ls_uid = $_SESSION["user_id"] ?? 0;
if ($__ls_uid > 0) {
    $___ls = mysqli_prepare($conn, "UPDATE users SET last_seen_at = NOW() WHERE id = ?");
    if ($___ls) {
        mysqli_stmt_bind_param($___ls, "i", $__ls_uid);
        mysqli_stmt_execute($___ls);
        mysqli_stmt_close($___ls);
    }
}


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";


/*|--------------------------------------------------------------------------| GET ACTIVITY LOGS|--------------------------------------------------------------------------|*/

$activities = [];

$query = "
    SELECT
        a.id,
        a.action,
        a.description,
        a.created_at,
        u.full_name,
        p.name AS project_name
    FROM activity_logs a
    INNER JOIN users u ON a.user_id = u.id
    LEFT JOIN projects p ON a.project_id = p.id
    ORDER BY a.created_at DESC
    LIMIT 50
";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $activities[] = $row;
    }
}


/*|--------------------------------------------------------------------------| ACTION ICONS|--------------------------------------------------------------------------|*/

function activity_icon($action) {
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
    <title>Activity Logs | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .activity-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-full-list {
            display: flex;
            flex-direction: column;
        }
        .activity-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .activity-row:last-child {
            border-bottom: none;
        }
        .activity-row-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 16px;
            flex-shrink: 0;
        }
        .activity-row-content {
            flex: 1;
        }
        .activity-row-content strong {
            display: block;
            margin-bottom: 3px;
        }
        .activity-row-content p {
            color: #6b7280;
            font-size: 13px;
            margin: 0 0 4px;
        }
        .activity-row-content small {
            color: #9ca3af;
            font-size: 11px;
        }
        .activity-action-badge {
            display: inline-flex;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            background: #f3f4f6;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>
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
            <a href="admin-users.php" class="nav-item"><span class="nav-icon">♙</span> Users</a>
            <a href="admin-projects.php" class="nav-item"><span class="nav-icon">▣</span> Projects</a>
            <p class="nav-title">MANAGEMENT</p>
            <a href="admin-reports.php" class="nav-item"><span class="nav-icon">▥</span> Reports</a>
            <a href="admin-activity.php" class="nav-item active"><span class="nav-icon">◷</span> Activity Logs</a>
            <p class="nav-title">ACCOUNT</p>
                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <a
                href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
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
                    <?= render_avatar($_SESSION["profile_image"] ?? null, $admin_name, (int)($_SESSION["user_id"])) ?>
                    <div class="profile-info"><strong><?= htmlspecialchars($admin_name) ?></strong><span>Administrator</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">ACTIVITY</span>
                    <h1>Activity Logs</h1>
                    <p>Recent activity across the system.</p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h2>Recent Activity</h2>
                        <p><?= count($activities) ?> recorded event(s)</p>
                    </div>
                </div>

                <?php if (!empty($activities)): ?>
                    <div class="activity-full-list">
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-row">
                                <div class="activity-row-icon"><?= activity_icon($activity["action"]) ?></div>
                                <div class="activity-row-content">
                                    <strong><?= htmlspecialchars($activity["full_name"]) ?></strong>
                                    <span class="activity-action-badge"><?= htmlspecialchars(str_replace("_", " ", $activity["action"])) ?></span>
                                    <p><?= htmlspecialchars($activity["description"]) ?></p>
                                    <?php if (!empty($activity["project_name"])): ?>
                                        <small>Project: <?= htmlspecialchars($activity["project_name"]) ?></small><br>
                                    <?php endif; ?>
                                    <small><?= htmlspecialchars($activity["created_at"]) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">◷</div>
                        <h3>No Activity Yet</h3>
                        <p>Activity will be recorded as actions occur in the system.</p>
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
