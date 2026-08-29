<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";

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



/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], ["admin", "project_manager", "member"])) {
    header("Location: dashboard.php");
    exit;
}


$user_id = (int) $_SESSION["user_id"];

$success = "";
$error = "";


/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$user = null;

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

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {

        $user = mysqli_fetch_assoc($result);

    }

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| USER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | UPDATE PERSONAL INFORMATION
    |--------------------------------------------------------------------------
    */

    if ($action === "update_profile") {

        $full_name = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");


        /*
        |----------------------------------------------------------------------
        | VALIDATION
        |----------------------------------------------------------------------
        */

        if ($full_name === "" || $email === "") {

            $error = "Full name and email are required.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | CHECK WHETHER EMAIL ALREADY EXISTS
            |--------------------------------------------------------------------------
            */

            $query = "
                SELECT id
                FROM users
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $email,
                    $user_id
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {

                    $error = "That email address is already being used.";

                }

                mysqli_stmt_close($stmt);
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            if ($error === "") {

                $query = "
                    UPDATE users
                    SET
                        full_name = ?,
                        email = ?
                    WHERE id = ?
                    LIMIT 1
                ";

                $stmt = mysqli_prepare($conn, $query);

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssi",
                        $full_name,
                        $email,
                        $user_id
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        /*
                        |------------------------------------------------------
                        | Update session information
                        |------------------------------------------------------
                        */

                        $_SESSION["full_name"] = $full_name;
                        $_SESSION["email"] = $email;

                        /*
                        |------------------------------------------------------
                        | Update local user data
                        |------------------------------------------------------
                        */

                        $user["full_name"] = $full_name;
                        $user["email"] = $email;

                        $success = "Your profile has been updated successfully.";

                    } else {

                        $error = "Unable to update your profile.";

                    }

                    mysqli_stmt_close($stmt);

                } else {

                    $error = "Database error. Please try again.";

                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($action === "change_password") {

        $current_password = $_POST["current_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            $current_password === "" ||
            $new_password === "" ||
            $confirm_password === ""
        ) {

            $error = "Please fill in all password fields.";

        } elseif (strlen($new_password) < 8) {

            $error = "The new password must contain at least 8 characters.";

        } elseif ($new_password !== $confirm_password) {

            $error = "The new passwords do not match.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | GET CURRENT PASSWORD
            |--------------------------------------------------------------------------
            */

            $query = "
                SELECT password
                FROM users
                WHERE id = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $query);

            $stored_password = null;

            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $user_id
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) === 1) {

                    $row = mysqli_fetch_assoc($result);

                    $stored_password = $row["password"];

                }

                mysqli_stmt_close($stmt);
            }


            /*
            |--------------------------------------------------------------------------
            | VERIFY CURRENT PASSWORD
            |--------------------------------------------------------------------------
            */

            if (!$stored_password) {

                $error = "Unable to verify your current password.";

            } elseif (!password_verify(
                $current_password,
                $stored_password
            )) {

                $error = "Your current password is incorrect.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | HASH NEW PASSWORD
                |--------------------------------------------------------------------------
                */

                $new_password_hash = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


                /*
                |--------------------------------------------------------------------------
                | UPDATE PASSWORD
                |--------------------------------------------------------------------------
                */

                $query = "
                    UPDATE users
                    SET password = ?
                    WHERE id = ?
                    LIMIT 1
                ";

                $stmt = mysqli_prepare($conn, $query);

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "si",
                        $new_password_hash,
                        $user_id
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        $success = "Your password has been changed successfully.";

                    } else {

                        $error = "Unable to change your password.";

                    }

                    mysqli_stmt_close($stmt);

                } else {

                    $error = "Database error. Please try again.";

                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FORMAT ROLE
|--------------------------------------------------------------------------
*/

$display_role = ucfirst(
    str_replace(
        "_",
        " ",
        $user["role"]
    )
);


/*
|--------------------------------------------------------------------------
| FORMAT STATUS
|--------------------------------------------------------------------------
*/

$display_status = ucfirst(
    $user["status"]
);


/*
|--------------------------------------------------------------------------
| AVATAR INITIALS
|--------------------------------------------------------------------------
*/

$name_parts = explode(
    " ",
    trim($user["full_name"])
);

if (count($name_parts) >= 2) {

    $initials =
        strtoupper(
            substr($name_parts[0], 0, 1) .
            substr($name_parts[count($name_parts) - 1], 0, 1)
        );

} else {

    $initials =
        strtoupper(
            substr($user["full_name"], 0, 2)
        );
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
        My Profile | PMS
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        .profile-page-grid {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 20px;
        }

        .profile-summary {
            text-align: center;
        }

        .large-profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 10px auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eef2ff;
            color: #4f46e5;

            font-size: 30px;
            font-weight: 700;
        }

        .profile-summary h2 {
            margin: 0 0 6px;
        }

        .profile-summary p {
            margin: 0 0 18px;
            color: #777;
        }

        .profile-meta {
            border-top: 1px solid #eee;
            margin-top: 20px;
            padding-top: 20px;
            text-align: left;
        }

        .profile-meta-item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 10px 0;
        }

        .profile-meta-item span {
            color: #777;
            font-size: 14px;
        }

        .profile-meta-item strong {
            text-align: right;
        }

        .profile-form {
            max-width: 700px;
        }

        .profile-form .form-group {
            margin-bottom: 18px;
        }

        .profile-form label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .profile-form input {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .profile-form input:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .profile-form button {
            border: none;
            cursor: pointer;
        }

        .password-note {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .profile-divider {
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }

        .alert-success {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fff1f2;
            color: #b91c1c;
            border: 1px solid #fecdd3;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .account-status {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .account-status.active {
            background: #ecfdf3;
            color: #166534;
        }

        .account-status.inactive {
            background: #fff1f2;
            color: #b91c1c;
        }

        @media (max-width: 900px) {

            .profile-page-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark')document.body.classList.add('dark');else if(t==='light')document.body.classList.remove('dark');})();
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

            <?php if ($_SESSION["role"] === "admin"): ?>

                <a href="admin-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> Projects
                </a>
                <a href="tasks.php" class="nav-item">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">MANAGEMENT</p>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">♙</span> Users
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>

            <?php elseif ($_SESSION["role"] === "project_manager"): ?>

                <a href="manager-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="manager-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> My Projects
                </a>
                <a href="manager-tasks.php" class="nav-item">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">WORKSPACE</p>
                <a href="manager-team.php" class="nav-item">
                    <span class="nav-icon">♙</span> Team
                </a>
                <a href="manager-reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>

            <?php else: ?>

                <a href="member-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="member-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> My Projects
                </a>
                <a href="member-tasks.php" class="nav-item">
                    <span class="nav-icon">✓</span> My Tasks
                </a>
                <p class="nav-title">COLLABORATION</p>
                <a href="team.php" class="nav-item">
                    <span class="nav-icon">♙</span> Team
                </a>

            <?php endif; ?>


                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <p class="nav-title">
                ACCOUNT
            </p>


            <a
                href="profile.php"
                class="nav-item active"
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
                    <span class="theme-icon-light">☀️</span>
                    <span class="theme-icon-dark">🌙</span>
                </button>
<button
                    class="notification-button"
                    type="button"
                    onclick="window.location.href='notifications.php'"
                    style="position:relative;"
                >
                    🔔
                    <span class="notification-dot" id="notifBadge" style="display:none;"></span>
                </button>


                <div class="admin-profile">


                    <div class="profile-avatar">

                        <?= htmlspecialchars($initials) ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $user["full_name"]
                            ) ?>

                        </strong>

                        <span>
                            <?= htmlspecialchars($display_role) ?>
                        </span>

                    </div>


                    <span class="profile-arrow">
                        ▾
                    </span>


                </div>


            </div>


        </header>



        <!-- =================================================
             PROFILE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        ACCOUNT
                    </span>

                    <h1>
                        My Profile
                    </h1>

                    <p>
                        Manage your personal information and account security.
                    </p>

                </div>


            </div>



            <!-- ALERTS -->

            <?php if ($success !== ""): ?>

                <div class="alert-success">

                    <?= htmlspecialchars($success) ?>

                </div>

            <?php endif; ?>


            <?php if ($error !== ""): ?>

                <div class="alert-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <div class="profile-page-grid">


                <!-- =================================================
                     PROFILE SUMMARY
                ================================================== -->

                <div class="dashboard-card profile-summary">


                    <div class="large-profile-avatar">

                        <?= htmlspecialchars($initials) ?>

                    </div>


                    <h2>

                        <?= htmlspecialchars(
                            $user["full_name"]
                        ) ?>

                    </h2>


                    <p>

                        <?= htmlspecialchars(
                            $user["email"]
                        ) ?>

                    </p>


                    <span class="status-badge">

                        <?= htmlspecialchars(
                            $display_role
                        ) ?>

                    </span>


                    <div class="profile-meta">


                        <div class="profile-meta-item">

                            <span>
                                Account Status
                            </span>

                            <strong>

                                <span
                                    class="account-status <?= htmlspecialchars(
                                        strtolower(
                                            $user["status"]
                                        )
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $display_status
                                    ) ?>

                                </span>

                            </strong>

                        </div>


                        <div class="profile-meta-item">

                            <span>
                                User ID
                            </span>

                            <strong>
                                #<?= (int) $user["id"] ?>
                            </strong>

                        </div>


                        <div class="profile-meta-item">

                            <span>
                                Role
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $display_role
                                ) ?>

                            </strong>

                        </div>


                    </div>


                </div>



                <!-- =================================================
                     PROFILE SETTINGS
                ================================================== -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <div>

                            <h2>
                                Personal Information
                            </h2>

                            <p>
                                Update your name and email address.
                            </p>

                        </div>


                    </div>


                    <form
                        method="POST"
                        class="profile-form"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="update_profile"
                        >


                        <div class="form-group">


                            <label for="full_name">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                value="<?= htmlspecialchars(
                                    $user["full_name"]
                                ) ?>"
                                required
                            >


                        </div>



                        <div class="form-group">


                            <label for="email">
                                Email Address
                            </label>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $user["email"]
                                ) ?>"
                                required
                            >


                        </div>



                        <button
                            type="submit"
                            class="primary-button"
                        >
                            Save Changes
                        </button>


                    </form>



                    <div class="profile-divider"></div>



                    <!-- =================================================
                         CHANGE PASSWORD
                    ================================================== -->

                    <div class="card-header">


                        <div>

                            <h2>
                                Change Password
                            </h2>

                            <p>
                                Update your password to keep your account secure.
                            </p>

                        </div>


                    </div>


                    <form
                        method="POST"
                        class="profile-form"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="change_password"
                        >


                        <div class="form-group">


                            <label for="current_password">
                                Current Password
                            </label>


                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                required
                            >


                        </div>



                        <div class="form-group">


                            <label for="new_password">
                                New Password
                            </label>


                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                minlength="8"
                                required
                            >


                            <div class="password-note">
                                Password must contain at least 8 characters.
                            </div>


                        </div>



                        <div class="form-group">


                            <label for="confirm_password">
                                Confirm New Password
                            </label>


                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                required
                            >


                        </div>



                        <button
                            type="submit"
                            class="primary-button"
                        >
                            Change Password
                        </button>


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