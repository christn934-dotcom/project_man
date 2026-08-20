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

    </style>

</head>


<body>


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
                href="#"
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


            <p class="nav-title">
                SYSTEM
            </p>


            <a
                href="#"
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
                    class="notification-button"
                    type="button"
                >
                    ♧
                </button>


                <div class="admin-profile">


                    <div class="profile-avatar">

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $_SESSION["full_name"],
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


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


                                                <div class="user-avatar">

                                                    <?= htmlspecialchars(
                                                        strtoupper(
                                                            substr(
                                                                $user["full_name"],
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>

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
                                                    ): ?>


                                                        <!-- CHANGE STATUS -->

                                                        <a
                                                            href="toggle-user.php?id=<?= (int) $user['id'] ?>"
                                                            onclick="return confirm('Change the status of this user?')"
                                                        >

                                                            <?= $user["status"] === "active"
                                                                ? "Deactivate"
                                                                : "Activate"
                                                            ?>

                                                        </a>



                                                        <!-- EDIT -->

                                                        <a
                                                            href="edit-user.php?id=<?= (int) $user['id'] ?>"
                                                        >
                                                            Edit User
                                                        </a>



                                                        <!-- DELETE -->

                                                        <a
                                                            href="delete-user.php?id=<?= (int) $user['id'] ?>"
                                                            class="danger-action"
                                                            onclick="return confirm('Are you sure you want to permanently delete this user?')"
                                                        >
                                                            Delete User
                                                        </a>


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


</body>

</html>