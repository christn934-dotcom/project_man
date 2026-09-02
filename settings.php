<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

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


if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";

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
        Settings | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .settings-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 20px;
        }

        .settings-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .settings-menu button {
            border: none;
            background: transparent;
            text-align: left;
            padding: 12px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }

        .settings-menu button:hover,
        .settings-menu button.active {
            background: #f1f5f9;
        }

        .settings-panel {
            display: none;
        }

        .settings-panel.active {
            display: block;
        }

        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #eee;
        }

        .setting-row:last-child {
            border-bottom: none;
        }

        .setting-info strong {
            display: block;
            margin-bottom: 5px;
        }

        .setting-info span {
            color: #777;
            font-size: 13px;
        }

        .setting-control select {
            min-width: 150px;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            background: white;
        }

        .toggle {
            position: relative;
            width: 46px;
            height: 24px;
            display: inline-block;
        }

        .toggle input {
            display: none;
        }

        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            transition: .2s;
        }

        .toggle-slider:before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: white;
            border-radius: 50%;
            transition: .2s;
        }

        .toggle input:checked + .toggle-slider {
            background: #111827;
        }

        .toggle input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        .settings-message {
            display: none;
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            background: #ecfdf5;
            color: #166534;
        }

        @media (max-width: 700px) {

            .settings-grid {
                grid-template-columns: 1fr;
            }

            .setting-row {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    /* Dark mode text overrides */
    body.dark .setting-info strong { color: #f1f5f9; }
    body.dark .setting-info span { color: #94a3b8; }
    body.dark .setting-row { border-bottom-color: rgba(255, 255, 255, 0.06); }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-logo">

            <div class="logo-icon">
                P
            </div>

            <div>

                <h2>PMS</h2>

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
                <span class="nav-icon">▦</span>
                Dashboard
            </a>


            <a
                href="projects.php"
                class="nav-item"
            >
                <span class="nav-icon">▣</span>
                Projects
            </a>


            <a
                href="tasks.php"
                class="nav-item"
            >
                <span class="nav-icon">✓</span>
                Tasks
            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="users.php"
                class="nav-item"
            >
                <span class="nav-icon">♙</span>
                Users
            </a>


            <a
                href="users.php?role=project_manager"
                class="nav-item"
            >
                <span class="nav-icon">♚</span>
                Project Managers
            </a>


            <a
                href="reports.php"
                class="nav-item"
            >
                <span class="nav-icon">▥</span>
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
                class="nav-item active"
            >
                <span class="nav-icon">⚙</span>
                Settings
            </a>


            <a
                href="profile.php"
                class="nav-item"
            >
                <span class="nav-icon">◉</span>
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
                <span>↪</span>
                Logout
            </a>

        </div>


    </aside>



    <!-- MAIN -->

    <main class="main-content">


        <header class="topbar">


            <div class="topbar-left">

                <button
                    class="mobile-menu"
                    type="button"
                    id="mobileMenuButton"
                >
                    ☰
                </button>


                <div class="search-box">

                    <span>⌕</span>

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

                    <?= render_avatar($_SESSION["profile_image"] ?? null, $admin_name, (int)($_SESSION["user_id"])) ?>


                    <div class="profile-info">

                        <strong>
                            <?= htmlspecialchars($admin_name) ?>
                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>

                </div>

            </div>


        </header>



        <section class="dashboard-content">


            <div class="page-header">

                <div>

                    <span class="page-label">
                        SYSTEM
                    </span>

                    <h1>
                        Settings
                    </h1>

                    <p>
                        Manage your PMS interface preferences.
                    </p>

                </div>

            </div>



            <div class="settings-grid">


                <!-- SETTINGS MENU -->

                <div class="dashboard-card">


                    <div class="settings-menu">

                        <button
                            type="button"
                            class="settings-tab active"
                            data-target="general"
                        >
                            General
                        </button>


                        <button
                            type="button"
                            class="settings-tab"
                            data-target="notifications"
                        >
                            Notifications
                        </button>


                        <button
                            type="button"
                            class="settings-tab"
                            data-target="display"
                        >
                            Display
                        </button>


                    </div>


                </div>



                <!-- SETTINGS CONTENT -->

                <div>


                    <!-- GENERAL -->

                    <div
                        class="dashboard-card settings-panel active"
                        id="general"
                    >

                        <div class="card-header">

                            <div>

                                <h2>
                                    General Settings
                                </h2>

                                <p>
                                    Basic system preferences.
                                </p>

                            </div>

                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    System Name
                                </strong>

                                <span>
                                    Name displayed throughout the system.
                                </span>

                            </div>


                            <div class="setting-control">

                                <strong>
                                    PMS
                                </strong>

                            </div>


                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Administrator
                                </strong>

                                <span>
                                    Current administrator account.
                                </span>

                            </div>


                            <div class="setting-control">

                                <strong>
                                    <?= htmlspecialchars($admin_name) ?>
                                </strong>

                            </div>


                        </div>


                    </div>



                    <!-- NOTIFICATIONS -->

                    <div
                        class="dashboard-card settings-panel"
                        id="notifications"
                    >

                        <div class="card-header">

                            <div>

                                <h2>
                                    Notifications
                                </h2>

                                <p>
                                    Control dashboard notification preferences.
                                </p>

                            </div>

                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Dashboard Notifications
                                </strong>

                                <span>
                                    Show notification indicators in the dashboard.
                                </span>

                            </div>


                            <label class="toggle">

                                <input
                                    type="checkbox"
                                    id="notificationsToggle"
                                    checked
                                >

                                <span class="toggle-slider"></span>

                            </label>


                        </div>                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Deadline Alerts
                                </strong>

                                <span>
                                    Show upcoming project deadline alerts.
                                </span>

                            </div>

                            <label class="toggle">

                                <input
                                    type="checkbox"
                                    id="deadlineToggle"
                                    checked
                                >

                                <span class="toggle-slider"></span>

                            </label>


                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Email Notifications
                                </strong>

                                <span>
                                    Send email alerts for task, project, and user events. Primary: PHP mail(). Fallback: Formspree.
                                </span>

                            </div>

                            <label class="toggle">

                                <input
                                    type="checkbox"
                                    id="emailNotifToggle"
                                    checked
                                >

                                <span class="toggle-slider"></span>

                            </label>


                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>                                    Formspree Form ID (Fallback)
                                </strong>
 
                                <span>
                                    Primary: PHP mail() sends to each user's email directly. Fallback: If mail() fails, Formspree sends to your inbox. Enter your Formspree form ID below (e.g. mnpqqobe).
                                </span>

                            </div>

                            <div class="setting-control" style="width:300px;">

                                <input
                                    type="text"
                                    id="formspreeId"
                                    placeholder="e.g. xrgbkzpl"
                                    style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;"
                                >

                            </div>


                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Email Events
                                </strong>

                                <span>
                                    Choose which events trigger email notifications.
                                </span>

                            </div>

                            <div class="setting-control" style="display:flex;flex-direction:column;gap:8px;">

                                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">

                                    <input type="checkbox" class="email-event" value="task_created" checked>
                                    Task Created
                                </label>

                                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">

                                    <input type="checkbox" class="email-event" value="task_updated" checked>
                                    Task Updated
                                </label>

                                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">

                                    <input type="checkbox" class="email-event" value="project_created" checked>
                                    Project Created
                                </label>

                                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">

                                    <input type="checkbox" class="email-event" value="project_updated" checked>
                                    Project Updated
                                </label>

                            </div>


                        </div>


                    </div>



                    <!-- DISPLAY -->

                    <div
                        class="dashboard-card settings-panel"
                        id="display"
                    >

                        <div class="card-header">

                            <div>

                                <h2>
                                    Display
                                </h2>

                                <p>
                                    Customize the appearance of your dashboard.
                                </p>

                            </div>

                        </div>


                        <div class="setting-row">

                            <div class="setting-info">

                                <strong>
                                    Theme
                                </strong>

                                <span>
                                    Switch between light and dark mode.
                                </span>

                            </div>

                            <label class="toggle">

                                <input
                                    type="checkbox"
                                    id="settingsThemeToggle"
                                    onclick="toggleSettingsTheme()"
                                >

                                <span class="toggle-slider"></span>

                            </label>

                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Table Rows
                                </strong>

                                <span>
                                    Number of rows shown on management pages.
                                </span>

                            </div>


                            <div class="setting-control">

                                <select id="rowsSetting">

                                    <option value="5">
                                        5
                                    </option>

                                    <option value="10">
                                        10
                                    </option>

                                    <option value="20">
                                        20
                                    </option>

                                    <option value="50">
                                        50
                                    </option>

                                </select>

                            </div>


                        </div>


                        <div class="setting-row">


                            <div class="setting-info">

                                <strong>
                                    Compact Tables
                                </strong>

                                <span>
                                    Use a more compact table layout.
                                </span>

                            </div>


                            <label class="toggle">

                                <input
                                    type="checkbox"
                                    id="compactToggle"
                                >

                                <span class="toggle-slider"></span>

                            </label>


                        </div>


                        <button
                            type="button"
                            class="primary-button"
                            id="saveSettings"
                        >
                            Save Preferences
                        </button>


                        <div
                            class="settings-message"
                            id="settingsMessage"
                        >
                            Settings saved successfully.
                        </div>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>


<script>

/*
|--------------------------------------------------------------------------
| SETTINGS TABS
|--------------------------------------------------------------------------
*/

const tabs =
    document.querySelectorAll(".settings-tab");

const panels =
    document.querySelectorAll(".settings-panel");

tabs.forEach(function(tab) {

    tab.addEventListener("click", function() {

        const target =
            this.dataset.target;

        tabs.forEach(function(item) {
            item.classList.remove("active");
        });

        panels.forEach(function(panel) {
            panel.classList.remove("active");
        });

        this.classList.add("active");

        const targetPanel =
            document.getElementById(target);

        if (targetPanel) {
            targetPanel.classList.add("active");
        }

    });

});


/*
|--------------------------------------------------------------------------
| SAVE DISPLAY PREFERENCES
|--------------------------------------------------------------------------
*/

const compactToggle =
    document.getElementById("compactToggle");

const rowsSetting =
    document.getElementById("rowsSetting");

const notificationsToggle =
    document.getElementById("notificationsToggle");

const deadlineToggle =
    document.getElementById("deadlineToggle");


if (compactToggle) {

    compactToggle.checked =
        localStorage.getItem("pms_compact_tables") === "true";

}


if (rowsSetting) {

    const savedRows =
        localStorage.getItem("pms_table_rows");

    if (savedRows) {
        rowsSetting.value = savedRows;
    }

}


if (notificationsToggle) {

    const saved =
        localStorage.getItem("pms_notifications");

    if (saved !== null) {
        notificationsToggle.checked =
            saved === "true";
    }

}


if (deadlineToggle) {

    const saved =
        localStorage.getItem("pms_deadlines");

    if (saved !== null) {
        deadlineToggle.checked =
            saved === "true";
    }

}


// Load notification settings from DB
var emailNotifToggle = document.getElementById("emailNotifToggle");
var formspreeId = document.getElementById("formspreeId");
var emailEvents = document.querySelectorAll(".email-event");

fetch('api/notification_settings.php')
    .then(function(r) { return r.json(); })
    .then(function(s) {
        if (emailNotifToggle && s.email_notifications_enabled !== undefined) emailNotifToggle.checked = s.email_notifications_enabled === '1';
        if (formspreeId && s.formspree_form_id) formspreeId.value = s.formspree_form_id;
        if (emailEvents.length > 0 && s.email_events) {
            var events = s.email_events.split(',');
            emailEvents.forEach(function(cb) { cb.checked = events.indexOf(cb.value) !== -1; });
        }
        if (s.deadline_alerts !== undefined && deadlineToggle) deadlineToggle.checked = s.deadline_alerts === '1';
        if (s.dashboard_notifications !== undefined && notificationsToggle) notificationsToggle.checked = s.dashboard_notifications === '1';
    })
    .catch(function() {});


const saveButton =
    document.getElementById("saveSettings");

if (saveButton) {

    saveButton.addEventListener("click", function() {

        localStorage.setItem(
            "pms_compact_tables",
            compactToggle.checked
        );

        localStorage.setItem(
            "pms_table_rows",
            rowsSetting.value
        );

        // Collect email notification settings for DB
        var selectedEvents = [];
        emailEvents.forEach(function(cb) {
            if (cb.checked) selectedEvents.push(cb.value);
        });

        var payload = {
            email_notifications_enabled: emailNotifToggle && emailNotifToggle.checked ? '1' : '0',
            formspree_form_id: formspreeId ? formspreeId.value : '',
            email_events: selectedEvents.join(','),
            deadline_alerts: deadlineToggle && deadlineToggle.checked ? '1' : '0',
            dashboard_notifications: notificationsToggle && notificationsToggle.checked ? '1' : '0'
        };

        fetch('api/notification_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var message = document.getElementById("settingsMessage");
            message.style.display = "block";
            setTimeout(function() { message.style.display = "none"; }, 2500);
        })
        .catch(function() {
            var message = document.getElementById("settingsMessage");
            message.textContent = 'Error saving. Saved locally.';
            message.style.background = '#fef3c7';
            message.style.color = '#92400e';
            message.style.display = "block";
            setTimeout(function() { message.style.display = "none"; message.textContent = 'Settings saved successfully.'; message.style.background = '#ecfdf5'; message.style.color = '#166534'; }, 2500);
        });

    });

}



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