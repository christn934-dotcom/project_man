<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
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


                                        <div class="table-avatar">

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

</body>

</html>