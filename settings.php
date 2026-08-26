<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
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


                <div class="admin-profile">

                    <div class="profile-avatar">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $admin_name,
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


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


                        </div>


                        <div class="setting-row">


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

        localStorage.setItem(
            "pms_notifications",
            notificationsToggle.checked
        );

        localStorage.setItem(
            "pms_deadlines",
            deadlineToggle.checked
        );

        const message =
            document.getElementById("settingsMessage");

        message.style.display = "block";

        setTimeout(function() {
            message.style.display = "none";
        }, 2500);

    });

}


/*
|--------------------------------------------------------------------------
| MOBILE SIDEBAR
|--------------------------------------------------------------------------
*/

const mobileMenuButton =
    document.getElementById("mobileMenuButton");

const sidebar =
    document.querySelector(".sidebar");

if (mobileMenuButton && sidebar) {

    mobileMenuButton.addEventListener(
        "click",
        function() {

            sidebar.classList.toggle(
                "mobile-open"
            );

        }
    );

}

</script>


</body>

</html>