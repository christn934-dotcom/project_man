<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| MEMBER PROTECTION|--------------------------------------------------------------------------|*/

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


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: dashboard.php");
    exit;
}


$member_id = (int) $_SESSION["user_id"];
$member_name = $_SESSION["full_name"] ?? "Team Member";


/*|--------------------------------------------------------------------------| GET TEAM MEMBERS|--------------------------------------------------------------------------|
| Get all members who share at least one project with the logged-in member
|--------------------------------------------------------------------------|*/

$team_members = [];

$query = "
    SELECT DISTINCT
        u.id,
        u.full_name,
        u.email,
        u.status,
                        profile_image
    FROM users u
    INNER JOIN project_members pm ON u.id = pm.user_id
    INNER JOIN projects p ON pm.project_id = p.id
    WHERE p.id IN (
        SELECT project_id FROM project_members WHERE user_id = ?
    )
    AND u.id != ?
    AND u.role = 'member'
    ORDER BY u.full_name ASC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $member_id, $member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $team_members[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}


/*|--------------------------------------------------------------------------| GET SHARED PROJECTS|--------------------------------------------------------------------------|*/

$shared_projects = [];

$query = "
    SELECT
        pm.user_id,
        p.name AS project_name
    FROM project_members pm
    INNER JOIN projects p ON pm.project_id = p.id
    WHERE p.id IN (
        SELECT project_id FROM project_members WHERE user_id = ?
    )
    AND pm.user_id != ?
    ORDER BY p.name ASC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $member_id, $member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $shared_projects[$row["user_id"]][] = $row["project_name"];
        }
    }

    mysqli_stmt_close($stmt);
}


$total_team = count($team_members);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .team-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .team-card { background: #fff; border: 1px solid #eef0f4; border-radius: 14px; padding: 22px; text-align: center; }
        .team-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .team-avatar { width: 56px; height: 56px; margin: 0 auto 14px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #eef2ff; color: #4f46e5; font-size: 18px; font-weight: 700; }
        .team-card h3 { font-size: 15px; margin-bottom: 4px; }
        .team-card p { color: #9ca3af; font-size: 12px; margin-bottom: 10px; }
        .team-projects { text-align: left; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 12px; }
        .team-projects small { display: block; color: #9ca3af; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.3px; }
        .team-project-item { display: block; color: #4f46e5; font-size: 12px; padding: 3px 0; }
        @media (max-width: 900px) { .team-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .team-grid { grid-template-columns: 1fr; } }
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
            <a href="member-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="member-projects.php" class="nav-item"><span class="nav-icon">▣</span> My Projects</a>
            <a href="member-tasks.php" class="nav-item"><span class="nav-icon">✓</span> My Tasks</a>
            <p class="nav-title">COLLABORATION</p>
            <a href="team.php" class="nav-item active"><span class="nav-icon">♙</span> Team</a>
            <a href="notifications.php" class="nav-item"><span class="nav-icon">♧</span> Notifications</a>
            <p class="nav-title">SYSTEM</p>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
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
                <div class="search-box"><span>⌕</span><input type="text" id="teamSearch" placeholder="Search team..."></div>
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
                    <?= render_avatar($_SESSION["profile_image"] ?? null, $member_name, (int)($_SESSION["user_id"])) ?>
                    <div class="profile-info"><strong><?= htmlspecialchars($member_name) ?></strong><span>Team Member</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">COLLABORATION</span>
                    <h1>Team</h1>
                    <p>Members you collaborate with on shared projects.</p>
                </div>
                <div class="page-actions">
                    <span class="project-count"><?= $total_team ?> Member<?= $total_team != 1 ? "s" : "" ?></span>
                </div>
            </div>

            <?php if (!empty($team_members)): ?>
                <div class="team-grid" id="teamGrid">
                    <?php foreach ($team_members as $tm): ?>
                        <div class="team-card" data-name="<?= htmlspecialchars(strtolower($tm["full_name"])) ?>">
                            <?= render_avatar($tm["profile_image"] ?? null, $tm["full_name"], (int)$tm["id"], 'md') ?>
                            <h3><?= htmlspecialchars($tm["full_name"]) ?></h3>
                            <p><?= htmlspecialchars($tm["email"]) ?></p>
                            <span class="status-badge status-completed">
                                <?= ucfirst(htmlspecialchars($tm["status"])) ?>
                            </span>
                            <?php if (!empty($shared_projects[$tm["id"]])): ?>
                                <div class="team-projects">
                                    <small>Shared Projects</small>
                                    <?php foreach ($shared_projects[$tm["id"]] as $pname): ?>
                                        <span class="team-project-item"><?= htmlspecialchars($pname) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-card">
                    <div class="empty-state">
                        <div class="empty-icon">♙</div>
                        <h3>No Team Members</h3>
                        <p>You don't share any projects with other team members yet.</p>
                    </div>
                </div>
            <?php endif; ?>

        </section>
    </main>

</div>

<script>
const searchInput = document.getElementById("teamSearch");
if (searchInput) {
    searchInput.addEventListener("input", function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll(".team-card").forEach(function (card) {
            card.style.display = card.dataset.name.includes(val) ? "" : "none";
        });
    });
}
</script><?php include "cookie_consent.php"; ?>
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
