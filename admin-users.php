<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
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


if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Users
|--------------------------------------------------------------------------
*/

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

if (!$result) {
    die("Error loading users: " . mysqli_error($conn));
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
        Manage Users | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

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
                href="admin-users.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="admin-projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                Projects

            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>


            <a
                href="admin-reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

            </a>


            <a
                href="admin-activity.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ◷
                </span>

                Activity Logs

            </a>


            <p class="nav-title">
                ACCOUNT
            </p>


                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
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


        <!-- TOPBAR -->

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


        <!-- =====================================================
             PAGE CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>

                    <span class="page-label">
                        USER MANAGEMENT
                    </span>

                    <h1>
                        Users
                    </h1>

                    <p>
                        Manage Project Managers and Team Members.
                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="admin-create-user.php"
                        class="primary-button"
                    >
                        + Add User
                    </a>

                </div>

            </div>


            <!-- =================================================
                 USERS TABLE
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">

                    <div>

                        <h2>
                            All Users
                        </h2>

                        <p>
                            Accounts registered in the system.
                        </p>

                    </div>

                </div>


                <div class="table-container">

                    <table
                        class="projects-table"
                        id="usersTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

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
                                    Created
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while (
                            $user = mysqli_fetch_assoc($result)
                        ): ?>


                            <tr>


                                <td>

                                    <?= (int) $user["id"] ?>

                                </td>


                                <td>

                                    <div class="user-table-info">


                                        <?= render_avatar($user["profile_image"] ?? null, $user["full_name"], (int)$user["id"]) ?>

                                        </div>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $user["full_name"]
                                            ) ?>

                                        </strong>


                                    </div>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $user["email"]
                                    ) ?>

                                </td>


                                <td>

                                    <?php if (
                                        $user["role"] === "admin"
                                    ): ?>

                                        <span class="role-badge admin">
                                            Admin
                                        </span>

                                    <?php elseif (
                                        $user["role"] === "project_manager"
                                    ): ?>

                                        <span class="role-badge manager">
                                            Project Manager
                                        </span>

                                    <?php else: ?>

                                        <span class="role-badge member">
                                            Team Member
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        $user["status"] === "active"
                                    ): ?>

                                        <span class="user-status active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="user-status inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= date(
                                        "M d, Y",
                                        strtotime(
                                            $user["created_at"]
                                        )
                                    ) ?>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>

                </div>


            </div>


        </section>

    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Search Users
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById("userSearch");


searchInput.addEventListener(
    "keyup",
    function () {

        const search =
            this.value
                .toLowerCase()
                .trim();


        const rows =
            document.querySelectorAll(
                "#usersTable tbody tr"
            );


        rows.forEach(
            function (row) {

                const text =
                    row.textContent
                        .toLowerCase();


                row.style.display =
                    text.includes(search)
                        ? ""
                        : "none";

            }
        );

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