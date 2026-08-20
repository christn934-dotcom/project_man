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


$error = "";


/*
|--------------------------------------------------------------------------
| Get Project Managers
|--------------------------------------------------------------------------
*/

$managers = [];

$query = "
    SELECT
        id,
        full_name,
        email
    FROM users
    WHERE role = 'project_manager'
    AND status = 'active'
    ORDER BY full_name ASC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $managers[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| Get Team Members
|--------------------------------------------------------------------------
*/

$members = [];

$query = "
    SELECT
        id,
        full_name,
        email
    FROM users
    WHERE role = 'member'
    AND status = 'active'
    ORDER BY full_name ASC
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $members[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");

    $description = trim(
        $_POST["description"] ?? ""
    );

    $start_date = $_POST["start_date"] ?? "";

    $end_date = $_POST["end_date"] ?? "";

    $status = $_POST["status"] ?? "planning";

    $priority = $_POST["priority"] ?? "medium";

    $manager_id = (int) (
        $_POST["manager_id"] ?? 0
    );

    $selected_members =
        $_POST["members"] ?? [];


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === "") {

        $error =
            "Project name is required.";

    }


    elseif ($start_date === "") {

        $error =
            "Start date is required.";

    }


    elseif ($manager_id <= 0) {

        $error =
            "Please select a Project Manager.";

    }


    elseif (
        !in_array(
            $status,
            [
                "planning",
                "in_progress",
                "on_hold",
                "completed",
                "cancelled"
            ],
            true
        )
    ) {

        $error =
            "Invalid project status.";

    }


    elseif (
        !in_array(
            $priority,
            [
                "low",
                "medium",
                "high",
                "urgent"
            ],
            true
        )
    ) {

        $error =
            "Invalid project priority.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Manager
    |--------------------------------------------------------------------------
    */

    else {

        $check_manager = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM users
            WHERE id = ?
            AND role = 'project_manager'
            AND status = 'active'
            LIMIT 1
            "
        );


        mysqli_stmt_bind_param(
            $check_manager,
            "i",
            $manager_id
        );


        mysqli_stmt_execute(
            $check_manager
        );


        $manager_result =
            mysqli_stmt_get_result(
                $check_manager
            );


        if (
            mysqli_num_rows(
                $manager_result
            ) === 0
        ) {

            $error =
                "The selected Project Manager is invalid.";

        }


        mysqli_stmt_close(
            $check_manager
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Team Members
    |--------------------------------------------------------------------------
    */

    if ($error === "" && !empty($selected_members)) {

        foreach (
            $selected_members
            as $member_id
        ) {

            $member_id = (int) $member_id;


            $check_member = mysqli_prepare(
                $conn,
                "
                SELECT id
                FROM users
                WHERE id = ?
                AND role = 'member'
                AND status = 'active'
                LIMIT 1
                "
            );


            mysqli_stmt_bind_param(
                $check_member,
                "i",
                $member_id
            );


            mysqli_stmt_execute(
                $check_member
            );


            $member_result =
                mysqli_stmt_get_result(
                    $check_member
                );


            if (
                mysqli_num_rows(
                    $member_result
                ) === 0
            ) {

                $error =
                    "One of the selected team members is invalid.";

                mysqli_stmt_close(
                    $check_member
                );

                break;

            }


            mysqli_stmt_close(
                $check_member
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Check End Date
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        &&
        $end_date !== ""
        &&
        $end_date < $start_date
    ) {

        $error =
            "End date cannot be before the start date.";

    }


    /*
    |--------------------------------------------------------------------------
    | Create Project
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        mysqli_begin_transaction(
            $conn
        );


        try {

            /*
            | Insert Project
            */

            $insert_project =
                mysqli_prepare(
                    $conn,
                    "
                    INSERT INTO projects
                    (
                        name,
                        description,
                        start_date,
                        end_date,
                        status,
                        priority,
                        manager_id,
                        created_by
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        NULLIF(?, ''),
                        ?,
                        ?,
                        ?,
                        ?
                    )
                    "
                );


            $admin_id =
                (int) $_SESSION["user_id"];


            mysqli_stmt_bind_param(
                $insert_project,
                "ssssssii",
                $name,
                $description,
                $start_date,
                $end_date,
                $status,
                $priority,
                $manager_id,
                $admin_id
            );


            if (
                !mysqli_stmt_execute(
                    $insert_project
                )
            ) {

                throw new Exception(
                    "Unable to create project."
                );

            }


            $project_id =
                mysqli_insert_id(
                    $conn
                );


            mysqli_stmt_close(
                $insert_project
            );


            /*
            |--------------------------------------------------------------------------
            | Add Team Members
            |--------------------------------------------------------------------------
            */

            if (!empty($selected_members)) {

                $insert_member =
                    mysqli_prepare(
                        $conn,
                        "
                        INSERT INTO project_members
                        (
                            project_id,
                            user_id
                        )
                        VALUES
                        (
                            ?,
                            ?
                        )
                        "
                    );


                foreach (
                    $selected_members
                    as $member_id
                ) {

                    $member_id =
                        (int) $member_id;


                    mysqli_stmt_bind_param(
                        $insert_member,
                        "ii",
                        $project_id,
                        $member_id
                    );


                    if (
                        !mysqli_stmt_execute(
                            $insert_member
                        )
                    ) {

                        throw new Exception(
                            "Unable to add team members."
                        );

                    }

                }


                mysqli_stmt_close(
                    $insert_member
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Activity Log
            |--------------------------------------------------------------------------
            */

            $action =
                "project_created";

            $log_description =
                "Created project: " . $name;


            $activity =
                mysqli_prepare(
                    $conn,
                    "
                    INSERT INTO activity_logs
                    (
                        user_id,
                        project_id,
                        action,
                        description
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                    "
                );


            mysqli_stmt_bind_param(
                $activity,
                "iiss",
                $admin_id,
                $project_id,
                $action,
                $log_description
            );


            if (
                !mysqli_stmt_execute(
                    $activity
                )
            ) {

                throw new Exception(
                    "Unable to create activity log."
                );

            }


            mysqli_stmt_close(
                $activity
            );


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            mysqli_commit(
                $conn
            );


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            header(
                "Location: admin-projects.php?created=1"
            );

            exit;


        } catch (Exception $e) {

            mysqli_rollback(
                $conn
            );

            $error =
                $e->getMessage();

        }

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
        Create Project | PMS
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
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Users

            </a>


            <a
                href="admin-projects.php"
                class="nav-item active"
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
         MAIN
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

            </div>


            <div class="topbar-right">

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

                </div>

            </div>

        </header>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <div class="page-header">

                <div>

                    <span class="page-label">
                        PROJECT MANAGEMENT
                    </span>

                    <h1>
                        Create Project
                    </h1>

                    <p>
                        Create a project and assign its manager and team members.
                    </p>

                </div>

            </div>


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="alert alert-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <div class="form-container project-form-container">

                <div class="dashboard-card">


                    <form
                        method="POST"
                        action=""
                    >


                        <!-- PROJECT NAME -->

                        <div class="form-group">

                            <label for="name">
                                Project Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= htmlspecialchars(
                                    $_POST["name"] ?? ""
                                ) ?>"
                                placeholder="Enter project name"
                                required
                            >

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <label for="description">
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                placeholder="Describe the project..."
                            ><?= htmlspecialchars(
                                $_POST["description"] ?? ""
                            ) ?></textarea>

                        </div>


                        <!-- DATES -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="start_date">
                                    Start Date
                                </label>

                                <input
                                    type="date"
                                    id="start_date"
                                    name="start_date"
                                    value="<?= htmlspecialchars(
                                        $_POST["start_date"] ?? ""
                                    ) ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="end_date">
                                    End Date
                                </label>

                                <input
                                    type="date"
                                    id="end_date"
                                    name="end_date"
                                    value="<?= htmlspecialchars(
                                        $_POST["end_date"] ?? ""
                                    ) ?>"
                                >

                            </div>

                        </div>


                        <!-- STATUS / PRIORITY -->

                        <div class="form-row">

                            <div class="form-group">

                                <label for="status">
                                    Status
                                </label>

                                <select
                                    id="status"
                                    name="status"
                                >

                                    <option
                                        value="planning"
                                    >
                                        Planning
                                    </option>

                                    <option
                                        value="in_progress"
                                    >
                                        In Progress
                                    </option>

                                    <option
                                        value="on_hold"
                                    >
                                        On Hold
                                    </option>

                                    <option
                                        value="completed"
                                    >
                                        Completed
                                    </option>

                                    <option
                                        value="cancelled"
                                    >
                                        Cancelled
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="priority">
                                    Priority
                                </label>

                                <select
                                    id="priority"
                                    name="priority"
                                >

                                    <option
                                        value="low"
                                    >
                                        Low
                                    </option>

                                    <option
                                        value="medium"
                                        selected
                                    >
                                        Medium
                                    </option>

                                    <option
                                        value="high"
                                    >
                                        High
                                    </option>

                                    <option
                                        value="urgent"
                                    >
                                        Urgent
                                    </option>

                                </select>

                            </div>

                        </div>


                        <!-- PROJECT MANAGER -->

                        <div class="form-group">

                            <label for="manager_id">
                                Project Manager
                            </label>

                            <select
                                id="manager_id"
                                name="manager_id"
                                required
                            >

                                <option value="">
                                    Select Project Manager
                                </option>


                                <?php foreach (
                                    $managers
                                    as $manager
                                ): ?>

                                    <option
                                        value="<?= $manager["id"] ?>"
                                        <?= (
                                            isset(
                                                $_POST["manager_id"]
                                            )
                                            &&
                                            $_POST["manager_id"]
                                            == $manager["id"]
                                        )
                                            ? "selected"
                                            : ""
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $manager["full_name"]
                                        ) ?>

                                        —
                                        <?= htmlspecialchars(
                                            $manager["email"]
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>


                            <?php if (
                                empty($managers)
                            ): ?>

                                <small
                                    style="color:#dc2626;"
                                >
                                    No active Project Managers found.
                                    Create one first.
                                </small>

                            <?php endif; ?>

                        </div>


                        <!-- TEAM MEMBERS -->

                        <div class="form-group">

                            <label>
                                Team Members
                            </label>

                            <div class="member-selection">


                                <?php if (
                                    empty($members)
                                ): ?>

                                    <p>
                                        No active Team Members found.
                                    </p>

                                <?php else: ?>


                                    <?php foreach (
                                        $members
                                        as $member
                                    ): ?>


                                        <label
                                            class="member-option"
                                        >

                                            <input
                                                type="checkbox"
                                                name="members[]"
                                                value="<?= $member["id"] ?>"
                                                <?= (
                                                    isset(
                                                        $_POST["members"]
                                                    )
                                                    &&
                                                    in_array(
                                                        $member["id"],
                                                        $_POST["members"]
                                                    )
                                                )
                                                    ? "checked"
                                                    : ""
                                                ?>
                                            >


                                            <span>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $member["full_name"]
                                                    ) ?>

                                                </strong>

                                                <small>

                                                    <?= htmlspecialchars(
                                                        $member["email"]
                                                    ) ?>

                                                </small>

                                            </span>

                                        </label>


                                    <?php endforeach; ?>


                                <?php endif; ?>


                            </div>

                        </div>


                        <!-- BUTTONS -->

                        <div class="form-actions">

                            <a
                                href="admin-dashboard.php"
                                class="secondary-button"
                            >
                                Cancel
                            </a>


                            <button
                                type="submit"
                                class="primary-button"
                                <?= empty($managers)
                                    ? "disabled"
                                    : ""
                                ?>
                            >
                                Create Project
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