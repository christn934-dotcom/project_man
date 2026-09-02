<?php
session_start();
require_once "config/database.php";
require_once "auth_check.php";
require_once "avatar_helper.php";;

$__ls_uid = $_SESSION["user_id"] ?? 0;
if ($__ls_uid > 0) {
    $___ls = mysqli_prepare($conn, "UPDATE users SET last_seen_at = NOW() WHERE id = ?");
    if ($___ls) { mysqli_stmt_bind_param($___ls, "i", $__ls_uid); mysqli_stmt_execute($___ls); mysqli_stmt_close($___ls); }
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: dashboard.php");
    exit;
}

$member_name = $_SESSION["full_name"] ?? "Team Member";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .settings-grid { display: grid; grid-template-columns: 220px 1fr; gap: 20px; }
        .settings-menu { display: flex; flex-direction: column; gap: 5px; }
        .settings-menu button { border: none; background: transparent; text-align: left; padding: 12px 14px; border-radius: 8px; cursor: pointer; font-size: 14px; }
        .settings-menu button:hover, .settings-menu button.active { background: #f1f5f9; }
        .settings-panel { display: none; }
        .settings-panel.active { display: block; }
        .setting-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 18px 0; border-bottom: 1px solid #eee; }
        .setting-row:last-child { border-bottom: none; }
        .setting-info strong { display: block; margin-bottom: 5px; }
        .setting-info span { color: #777; font-size: 13px; }
        .toggle { position: relative; width: 46px; height: 24px; display: inline-block; }
        .toggle input { display: none; }
        .toggle-slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 20px; cursor: pointer; transition: .2s; }
        .toggle-slider:before { content: ""; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: .2s; }
        .toggle input:checked + .toggle-slider { background: #111827; }
        .toggle input:checked + .toggle-slider:before { transform: translateX(22px); }
        .settings-message { display: none; margin-top: 15px; padding: 12px; border-radius: 8px; background: #ecfdf5; color: #166534; }
        @media (max-width: 700px) { .settings-grid { grid-template-columns: 1fr; } .setting-row { align-items: flex-start; flex-direction: column; } }
    /* Dark mode text overrides */
    body.dark .setting-info strong { color: #f1f5f9; }
    body.dark .setting-info span { color: #94a3b8; }
    body.dark .setting-row { border-bottom-color: rgba(255, 255, 255, 0.06); }

    </style>
</head>
<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
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
            <a href="member-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="member-projects.php" class="nav-item"><span class="nav-icon">▣</span> My Projects</a>
            <a href="member-tasks.php" class="nav-item"><span class="nav-icon">✓</span> My Tasks</a>
            <p class="nav-title">ACCOUNT</p>
            <a href="notifications.php" class="nav-item"><span class="nav-icon">♧</span> Notifications</a>
            <a href="member-settings.php" class="nav-item active"><span class="nav-icon">⚙</span> Settings</a>
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

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" id="mobileMenuButton">☰</button>
                <div class="search-box"><span>⌕</span><input type="text" placeholder="Search..."></div>
            </div>
            <div class="topbar-right">
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                    <span class="theme-icon-light"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg></span>
                    <span class="theme-icon-dark"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></span>
                </button>
                <button class="notification-button" type="button" onclick="window.location.href='notifications.php'" style="position:relative;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="notification-dot" id="notifBadge" style="display:none;"></span>
                </button>
                <div class="admin-profile">
                    <?= render_avatar($_SESSION["profile_image"] ?? null, $member_name, (int)($_SESSION["user_id"])) ?>
                    <div class="profile-info">
                        <strong><?= htmlspecialchars($member_name) ?></strong>
                        <span>Team Member</span>
                    </div>
                </div>
            </div>
        </header>

        <section class="dashboard-content">
            <div class="page-header">
                <div>
                    <span class="page-label">ACCOUNT</span>
                    <h1>Settings</h1>
                    <p>Customize your dashboard appearance.</p>
                </div>
            </div>

            <div class="settings-grid">
                <div class="dashboard-card">
                    <div class="settings-menu">
                        <button type="button" class="settings-tab active" data-target="display">Display</button>
                    </div>
                </div>

                <div>
                    <div class="dashboard-card settings-panel active" id="display">
                        <div class="card-header">
                            <div>
                                <h2>Display</h2>
                                <p>Customize the appearance of your dashboard.</p>
                            </div>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <strong>Theme</strong>
                                <span>Switch between light and dark mode.</span>
                            </div>
                            <label class="toggle">
                                <input type="checkbox" id="settingsThemeToggle" onclick="toggleSettingsTheme()">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="setting-row">
                            <div class="setting-info">
                                <strong>Table Rows</strong>
                                <span>Number of rows shown on management pages.</span>
                            </div>
                            <div class="setting-control">
                                <select id="rowsSetting">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>

                        <button type="button" class="primary-button" id="saveSettings">Save Preferences</button>
                        <div class="settings-message" id="settingsMessage">Settings saved successfully.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<?php include "cookie_consent.php"; ?>
<script src="dark_mode.php"></script>
<script src="assets/js/responsive.js"></script>
<script>
// Settings tabs
document.querySelectorAll(".settings-tab").forEach(function(tab) {
    tab.addEventListener("click", function() {
        document.querySelectorAll(".settings-tab").forEach(function(t) { t.classList.remove("active"); });
        document.querySelectorAll(".settings-panel").forEach(function(p) { p.classList.remove("active"); });
        this.classList.add("active");
        var panel = document.getElementById(this.dataset.target);
        if (panel) panel.classList.add("active");
    });
});

// Load saved rows
var rowsSetting = document.getElementById("rowsSetting");
if (rowsSetting) { var saved = localStorage.getItem("pms_table_rows"); if (saved) rowsSetting.value = saved; }

// Save
document.getElementById("saveSettings").addEventListener("click", function() {
    localStorage.setItem("pms_table_rows", rowsSetting.value);
    var msg = document.getElementById("settingsMessage");
    msg.style.display = "block";
    setTimeout(function() { msg.style.display = "none"; }, 2500);
});

// Notification badge
(function() {
    fetch('notification_count.php').then(function(r) { return r.json(); }).then(function(data) {
        var badge = document.getElementById('notifBadge');
        if (badge && data.count > 0) {
            badge.style.display = 'block';
            badge.textContent = data.count > 99 ? '99+' : data.count;
            badge.style.width = 'auto'; badge.style.height = 'auto';
            badge.style.padding = '1px 5px'; badge.style.fontSize = '10px';
            badge.style.borderRadius = '10px'; badge.style.fontWeight = '700';
        }
    }).catch(function(){});
})();
</script>
</body>
</html>
