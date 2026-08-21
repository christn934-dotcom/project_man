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
                href="#"
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


            <p class="nav-title">
                SYSTEM
            </p>


            <a
                href="#"
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


</body>

</html>