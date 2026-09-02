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

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USERS
|--------------------------------------------------------------------------
*/

$users = [];

$query = "
    SELECT
        id,
        full_name,
        email,
        role,
                        profile_image,
        status,
        created_at
    FROM users
    ORDER BY created_at DESC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| USER COUNTS
|--------------------------------------------------------------------------
*/

$total_users = count($users);

$admin_count = 0;
$manager_count = 0;
$member_count = 0;
$active_count = 0;

foreach ($users as $user) {

    if ($user["role"] === "admin") {
        $admin_count++;
    }

    if ($user["role"] === "project_manager") {
        $manager_count++;
    }

    if ($user["role"] === "member") {
        $member_count++;
    }

    if ($user["status"] === "active") {
        $active_count++;
    }

}

/*
|--------------------------------------------------------------------------
| ACTION MESSAGES
|--------------------------------------------------------------------------
*/

$success_message = "";
$error_message = "";

if (isset($_GET["updated"])) {

    if ($_GET["updated"] == "1") {
        $success_message = "User updated successfully.";
    }

}

if (isset($_GET["deleted"])) {

    if ($_GET["deleted"] == "1") {
        $success_message = "User deleted successfully.";
    }

}

if (isset($_GET["status_changed"])) {

    if ($_GET["status_changed"] == "1") {
        $success_message = "User status changed successfully.";
    }

}

if (isset($_GET["error"])) {

    switch ($_GET["error"]) {

        case "admin_protected":
            $error_message =
                "The administrator account is protected and cannot be modified or deleted.";
            break;

        case "user_not_found":
            $error_message =
                "The selected user could not be found.";
            break;

        case "invalid_user":
            $error_message =
                "Invalid user selected.";
            break;

        case "status_change_failed":
            $error_message =
                "Unable to change the user's status.";
            break;

        case "delete_failed":
            $error_message =
                "Unable to delete the user.";
            break;

        default:
            $error_message =
                "An unexpected error occurred.";
            break;

    }

}

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
        Users | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /* =====================================================
           USER ACTION MENU
        ===================================================== */

        .user-actions {
            position: relative;
            display: inline-block;
        }

        .user-action-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);

            min-width: 180px;

            background: #ffffff;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);

            padding: 6px;

            display: none;

            z-index: 999;
        }

        .user-action-menu.show {
            display: block;
        }

        .user-action-menu a,
        .protected-action {
            display: block;

            width: 100%;

            box-sizing: border-box;

            padding: 10px 12px;

            border-radius: 7px;

            text-decoration: none;

            font-size: 14px;

            color: #374151;
        }

        .user-action-menu a:hover {
            background: #f3f4f6;
        }

        .user-action-menu .danger-action {
            color: #dc2626;
        }

        .user-action-menu .danger-action:hover {
            background: #fef2f2;
        }

        .protected-action {
            color: #6b7280;
            cursor: default;
        }

        .table-action {
            width: 34px;
            height: 34px;

            border: none;

            background: transparent;

            border-radius: 7px;

            cursor: pointer;

            font-size: 20px;

            color: #6b7280;
        }

        .table-action:hover {
            background: #f3f4f6;
            color: #111827;
        }

        /* =====================================================
           USER AVATAR
        ===================================================== */

        .user-avatar {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: 600;

            font-size: 14px;

            background: #eef2ff;
            color: #4f46e5;
        }

        /* =====================================================
           USER NAME
        ===================================================== */

        .user-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-name-details {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .user-name-details strong {
            font-size: 14px;
        }

        .user-name-details span {
            font-size: 12px;
            color: #9ca3af;
        }

        /* =====================================================
           PROTECTED ADMIN
        ===================================================== */

        .admin-protected {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 6px 10px;

            border-radius: 7px;

            background: #f3f4f6;

            color: #6b7280;

            font-size: 12px;
        }

        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        /* =====================================================
   ALERT MESSAGES
===================================================== */

.alert-message {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 14px;

    font-weight: 500;
}

.success-alert {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #047857;
}

.error-alert {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}

.alert-icon {
    width: 25px;
    height: 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    font-weight: 700;
}

    /* Dark mode text overrides */
    body.dark .user-name-details strong { color: #f1f5f9; }
    body.dark .user-name-details span { color: #94a3b8; }
    body.dark .protected-action { color: #64748b; }
    body.dark .table-action { color: #94a3b8; }
    body.dark .table-action:hover { background: rgba(99, 102, 241, 0.1); color: #e2e8f0; }
    body.dark .role-badge { color: #e2e8f0; }
    body.dark .alert-success { background: rgba(22, 101, 52, 0.15); color: #6ee7b7; border-color: rgba(110, 231, 183, 0.2); }

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
                href="admin-dashboard.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Projects

            </a>


            <a
                href="tasks.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Tasks

            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="users.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="users.php?role=project_manager"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♚
                </span>

                Project Managers

            </a>


            <a
                href="reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

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


        <!-- =================================================
             TOPBAR
        ================================================== -->

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
                        id="userSearch"
                        placeholder="Search users..."
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


                    <?= render_avatar($_SESSION["profile_image"] ?? null, $_SESSION["full_name"], (int)($_SESSION["user_id"] ?? 0)) ?>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $_SESSION["full_name"]
                            ) ?>

                        </strong>

                        <span>
                            Administrator
                        </span>

                    </div>


                    <span class="profile-arrow">
                        ▾
                    </span>


                </div>


            </div>


        </header>



        <!-- =================================================
             CONTENT
        ================================================== -->

        <section class="dashboard-content">

<?php if ($success_message !== ""): ?>

    <div class="alert-message success-alert">

        <span class="alert-icon">
            ✓
        </span>

        <span>
            <?= htmlspecialchars($success_message) ?>
        </span>

    </div>

<?php endif; ?>


<?php if ($error_message !== ""): ?>

    <div class="alert-message error-alert">

        <span class="alert-icon">
            !
        </span>

        <span>
            <?= htmlspecialchars($error_message) ?>
        </span>

    </div>

<?php endif; ?>
            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        MANAGEMENT
                    </span>


                    <h1>
                        Users
                    </h1>


                    <p>
                        Manage project managers and team members.
                    </p>

                </div>


                <div class="page-actions">

                    <button
                        class="primary-button"
                        type="button"
                        onclick="openUserModal()"
                    >
                        + Add User
                    </button>

                </div>


            </div>



            <!-- =================================================
                 USER STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <div class="stat-card">

                    <div class="stat-icon">
                        ♙
                    </div>

                    <div class="stat-info">

                        <span>
                            Total Users
                        </span>

                        <strong>
                            <?= $total_users ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon">
                        ♚
                    </div>

                    <div class="stat-info">

                        <span>
                            Project Managers
                        </span>

                        <strong>
                            <?= $manager_count ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-info">

                        <span>
                            Team Members
                        </span>

                        <strong>
                            <?= $member_count ?>
                        </strong>

                    </div>

                </div>



                <div class="stat-card">

                    <div class="stat-icon">
                        ✓
                    </div>

                    <div class="stat-info">

                        <span>
                            Active Users
                        </span>

                        <strong>
                            <?= $active_count ?>
                        </strong>

                    </div>

                </div>


            </div>



            <!-- =================================================
                 USERS TABLE
            ================================================== -->

            <div class="dashboard-card project-table-card">


                <div class="card-header">


                    <div>

                        <h2>
                            All Users
                        </h2>

                        <p>
                            <?= $total_users ?> user(s)
                        </p>

                    </div>


                </div>



                <?php if ($total_users > 0): ?>


                    <div class="table-container">


                        <table
                            class="projects-table"
                            id="usersTable"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        User
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Role
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Joined
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $users as $user
                                ): ?>


                                    <tr
                                        data-role="<?= htmlspecialchars(
                                            $user["role"]
                                        ) ?>"
                                    >


                                        <!-- USER -->

                                        <td>


                                            <div class="user-name">


                                                <?= render_avatar($user["profile_image"] ?? null, $user["full_name"], (int)$user["id"]) ?>

                                                </div>


                                                <div class="user-name-details">


                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $user["full_name"]
                                                        ) ?>

                                                    </strong>


                                                    <?php if (
                                                        $user["id"]
                                                        == $_SESSION["user_id"]
                                                    ): ?>

                                                        <span>
                                                            You
                                                        </span>

                                                    <?php else: ?>

                                                        <span>
                                                            PMS User
                                                        </span>

                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>



                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $user["email"]
                                            ) ?>

                                        </td>



                                        <!-- ROLE -->

                                        <td>


                                            <?php if (
                                                $user["role"]
                                                === "admin"
                                            ): ?>

                                                <span
                                                    class="status-badge status-completed"
                                                >
                                                    Administrator
                                                </span>


                                            <?php elseif (
                                                $user["role"]
                                                === "project_manager"
                                            ): ?>

                                                <span
                                                    class="status-badge status-in_progress"
                                                >
                                                    Project Manager
                                                </span>


                                            <?php else: ?>

                                                <span
                                                    class="status-badge status-planning"
                                                >
                                                    Team Member
                                                </span>

                                            <?php endif; ?>


                                        </td>



                                        <!-- STATUS -->

                                        <td>


                                            <?php if (
                                                $user["status"]
                                                === "active"
                                            ): ?>

                                                <span
                                                    class="status-badge status-completed"
                                                >
                                                    Active
                                                </span>


                                            <?php else: ?>

                                                <span
                                                    class="status-badge status-on_hold"
                                                >
                                                    Inactive
                                                </span>

                                            <?php endif; ?>


                                        </td>



                                        <!-- JOINED -->

                                        <td>

                                            <?= htmlspecialchars(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $user["created_at"]
                                                    )
                                                )
                                            ) ?>

                                        </td>



                                        <!-- ACTION -->

                                        <td>


                                            <div class="user-actions">


                                                <button
                                                    type="button"
                                                    class="table-action"
                                                    onclick="toggleUserMenu(<?= (int) $user['id'] ?>)"
                                                >
                                                    ⋮
                                                </button>



                                                <div
                                                    class="user-action-menu"
                                                    id="userMenu<?= (int) $user['id'] ?>"
                                                >


                                                    <?php if (
                                                        $user["id"]
                                                        != $_SESSION["user_id"]
                                                    ): ?>                                                        <!-- CHANGE STATUS -->
                                                        <form method="POST" action="toggle-user.php" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                            <button type="submit" class="danger-action" onclick="return confirm('Change the status of this user?')" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font:inherit;">
                                                                <?= $user["status"] === "active" ? "Deactivate" : "Activate" ?>
                                                            </button>
                                                        </form>



                                                        <!-- EDIT -->
                                                        <a
                                                            href="edit-user.php?id=<?= (int) $user['id'] ?>"
                                                        >
                                                            Edit User
                                                        </a>



                                                        <!-- DELETE -->
                                                        <form method="POST" action="delete-user.php" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                                            <button type="submit" class="danger-action" onclick="return confirm('Are you sure you want to permanently delete this user?')" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font:inherit;">
                                                                Delete User
                                                            </button>
                                                        </form>


                                                    <?php else: ?>


                                                        <!-- ADMIN PROTECTION -->

                                                        <span class="protected-action">

                                                            🔒 Admin Account

                                                        </span>


                                                    <?php endif; ?>


                                                </div>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">


                        <div class="empty-icon">
                            ♙
                        </div>


                        <h3>
                            No users found
                        </h3>


                        <p>
                            Add a Project Manager or Team Member.
                        </p>


                        <button
                            class="primary-button"
                            type="button"
                            onclick="openUserModal()"
                        >
                            + Add User
                        </button>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<!-- =====================================================
     ADD USER MODAL
====================================================== -->

<div
    class="modal-overlay"
    id="userModal"
>


    <div class="modal">


        <div class="modal-header">


            <div>

                <h2>
                    Add New User
                </h2>

                <p>
                    Create a Project Manager or Team Member account.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="closeUserModal()"
            >
                ×
            </button>


        </div>



        <form
            action="create-user.php"
            method="POST"
        >


            <!-- FULL NAME -->

            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="e.g. John Manager"
                    minlength="2"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="e.g. john@pms.com"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    minlength="8"
                    required
                >

            </div>



            <!-- ROLE -->

            <div class="form-group">

                <label>
                    Role
                </label>

                <select
                    name="role"
                    required
                >

                    <option value="member">
                        Team Member
                    </option>

                    <option value="project_manager">
                        Project Manager
                    </option>

                </select>


                <small class="form-warning">
                    The Admin role cannot be created here.
                </small>


            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="secondary-button"
                    onclick="closeUserModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="primary-button"
                >
                    Create User
                </button>


            </div>


        </form>


    </div>


</div>



<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| OPEN USER MODAL
|--------------------------------------------------------------------------
*/

function openUserModal() {

    document
        .getElementById("userModal")
        .classList.add("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE USER MODAL
|--------------------------------------------------------------------------
*/

function closeUserModal() {

    document
        .getElementById("userModal")
        .classList.remove("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

window.addEventListener(
    "click",
    function(event) {

        const modal =
            document.getElementById("userModal");

        if (event.target === modal) {

            closeUserModal();

        }

    }
);


/*
|--------------------------------------------------------------------------
| USER SEARCH
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById("userSearch");

if (searchInput) {

    searchInput.addEventListener(
        "keyup",
        function() {

            const search =
                this.value.toLowerCase();

            const rows =
                document.querySelectorAll(
                    "#usersTable tbody tr"
                );

            rows.forEach(
                function(row) {

                    const text =
                        row.textContent.toLowerCase();

                    row.style.display =
                        text.includes(search)
                            ? ""
                            : "none";

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| USER ACTION MENU
|--------------------------------------------------------------------------
*/

function toggleUserMenu(userId) {

    const menu =
        document.getElementById(
            "userMenu" + userId
        );

    if (!menu) {
        return;
    }


    /*
    | Close all other menus
    */

    document
        .querySelectorAll(".user-action-menu")
        .forEach(
            function(item) {

                if (item !== menu) {

                    item.classList.remove("show");

                }

            }
        );


    /*
    | Toggle selected menu
    */

    menu.classList.toggle("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE USER ACTION MENUS WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "click",
    function(event) {

        if (
            !event.target.closest(".user-actions")
        ) {

            document
                .querySelectorAll(
                    ".user-action-menu"
                )
                .forEach(
                    function(menu) {

                        menu.classList.remove(
                            "show"
                        );

                    }
                );

        }

    }
);

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