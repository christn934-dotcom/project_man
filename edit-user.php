<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "send_email_notification.php";

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($user_id <= 0) {
    header("Location: users.php?error=invalid_user");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        id,
        full_name,
        email,
        role,
        status
    FROM users
    WHERE id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$user) {
    header("Location: users.php?error=user_not_found");
    exit;
}


/*
|--------------------------------------------------------------------------
| PROTECT ADMIN ROLE
|--------------------------------------------------------------------------
|
| The system has only ONE admin.
| The Admin account cannot be changed into another role.
|--------------------------------------------------------------------------
*/

$is_admin_account =
    $user["role"] === "admin";


/*
|--------------------------------------------------------------------------
| UPDATE USER
|--------------------------------------------------------------------------
*/

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $role = $_POST["role"] ?? "";
    $status = $_POST["status"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($full_name === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email address is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }


    /*
    |--------------------------------------------------------------------------
    | ROLE VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($is_admin_account) {

        /*
        | Admin must remain Admin
        */

        $role = "admin";

    } else {

        if (
            $role !== "project_manager" &&
            $role !== "member"
        ) {
            $errors[] = "Invalid user role.";
        }

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $status !== "active" &&
        $status !== "inactive"
    ) {
        $errors[] = "Invalid user status.";
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $query = "
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $user_id
        );

        mysqli_stmt_execute($stmt);

        $result =
            mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {

            $errors[] =
                "That email address is already being used.";

        }

        mysqli_stmt_close($stmt);

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DATABASE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $query = "
            UPDATE users
            SET
                full_name = ?,
                email = ?,
                role = ?,
                status = ?
            WHERE id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $full_name,
            $email,
            $role,
            $status,
            $user_id
        );

        $success =
            mysqli_stmt_execute($stmt);

        mysqli_stmt_close($stmt);


        if ($success) {

            /* Activity log */
            $actor_id = (int) $_SESSION["user_id"];
            $log_desc = "Updated user: " . $full_name . " (" . $email . ")";
            $log_action = "user_updated";
            $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
            if ($lstmt) {
                mysqli_stmt_bind_param($lstmt, "iss", $actor_id, $log_action, $log_desc);
                mysqli_stmt_execute($lstmt);
                mysqli_stmt_close($lstmt);
            }

            header(
                "Location: users.php?updated=1"
            );

            exit;

        } else {

            $errors[] =
                "Unable to update the user.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | KEEP FORM VALUES AFTER ERROR
    |--------------------------------------------------------------------------
    */

    $user["full_name"] = $full_name;
    $user["email"] = $email;
    $user["role"] = $role;
    $user["status"] = $status;

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
        Edit User | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | EDIT USER PAGE
        |--------------------------------------------------------------------------
        */

        .edit-user-container {
            max-width: 760px;
            margin: 0 auto;
        }

        .edit-user-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 30px;
        }

        .edit-user-header {
            margin-bottom: 25px;
        }

        .edit-user-header h2 {
            margin: 0 0 6px;
        }

        .edit-user-header p {
            margin: 0;
            color: #6b7280;
        }

        .user-role-notice {
            padding: 14px 16px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #4b5563;
            margin-bottom: 22px;
            font-size: 14px;
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .edit-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .edit-user-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .back-link {
            text-decoration: none;
            color: #6b7280;
            font-size: 14px;
        }

        .back-link:hover {
            color: #111827;
        }

        @media (max-width: 700px) {

            .edit-form-row {
                grid-template-columns: 1fr;
            }

            .edit-user-card {
                padding: 20px;
            }

            .edit-user-actions {
                flex-direction: column-reverse;
            }

            .edit-user-actions button,
            .edit-user-actions a {
                width: 100%;
                text-align: center;
            }

        }

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
                class="nav-item active"
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
                class="nav-item"
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
             PAGE
        ====================================================== -->

        <section class="dashboard-content">


            <div class="page-header">


                <div>

                    <span class="page-label">
                        MANAGEMENT
                    </span>

                    <h1>
                        Edit User
                    </h1>

                    <p>
                        Update this user's account information.
                    </p>

                </div>


            </div>



            <div class="edit-user-container">


                <div class="edit-user-card">


                    <div class="edit-user-header">

                        <h2>
                            User Information
                        </h2>

                        <p>
                            Editing:
                            <strong>
                                <?= htmlspecialchars(
                                    $user["full_name"]
                                ) ?>
                            </strong>
                        </p>

                    </div>



                    <?php if (!empty($errors)): ?>

                        <div class="error-box">

                            <ul>

                                <?php foreach (
                                    $errors as $error
                                ): ?>

                                    <li>
                                        <?= htmlspecialchars($error) ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>



                    <?php if ($is_admin_account): ?>

                        <div class="user-role-notice">

                            🔒 This is the system administrator account.
                            The administrator role cannot be changed.

                        </div>

                    <?php endif; ?>



                    <form
                        method="POST"
                        action=""
                    >


                        <!-- FULL NAME -->

                        <div class="form-group">

                            <label>
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                value="<?= htmlspecialchars(
                                    $user["full_name"]
                                ) ?>"
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
                                value="<?= htmlspecialchars(
                                    $user["email"]
                                ) ?>"
                                required
                            >

                        </div>



                        <!-- ROLE + STATUS -->

                        <div class="edit-form-row">


                            <div class="form-group">

                                <label>
                                    Role
                                </label>

                                <?php if ($is_admin_account): ?>

                                    <input
                                        type="text"
                                        value="Administrator"
                                        disabled
                                    >

                                    <input
                                        type="hidden"
                                        name="role"
                                        value="admin"
                                    >

                                <?php else: ?>

                                    <select
                                        name="role"
                                        required
                                    >

                                        <option
                                            value="member"
                                            <?= $user["role"] === "member"
                                                ? "selected"
                                                : "" ?>
                                        >
                                            Team Member
                                        </option>

                                        <option
                                            value="project_manager"
                                            <?= $user["role"] === "project_manager"
                                                ? "selected"
                                                : "" ?>
                                        >
                                            Project Manager
                                        </option>

                                    </select>

                                <?php endif; ?>

                            </div>



                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <select
                                    name="status"
                                    required
                                >

                                    <option
                                        value="active"
                                        <?= $user["status"] === "active"
                                            ? "selected"
                                            : "" ?>
                                    >
                                        Active
                                    </option>

                                    <option
                                        value="inactive"
                                        <?= $user["status"] === "inactive"
                                            ? "selected"
                                            : "" ?>
                                    >
                                        Inactive
                                    </option>

                                </select>

                            </div>


                        </div>



                        <!-- ACTIONS -->

                        <div class="edit-user-actions">


                            <a
                                href="users.php"
                                class="secondary-button"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="primary-button"
                            >
                                Save Changes
                            </button>


                        </div>


                    </form>


                </div>


            </div>


        </section>


    </main>


</div>


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