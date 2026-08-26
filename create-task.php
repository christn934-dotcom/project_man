<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "project_manager"
) {

    header("Location: dashboard.php");
    exit;

}


$manager_id = (int) $_SESSION["user_id"];

$error = "";

$success = "";


/*
|--------------------------------------------------------------------------
| GET MANAGER'S PROJECTS
|--------------------------------------------------------------------------
*/

$projects = [];

$query = "
    SELECT
        id,
        name
    FROM projects
    WHERE manager_id = ?
    ORDER BY name ASC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $manager_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $projects[] = $row;

        }

    }

    mysqli_stmt_close($stmt);

}


/*
|--------------------------------------------------------------------------
| GET ACTIVE TEAM MEMBERS
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
| CREATE TASK
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $project_id = (int) ($_POST["project_id"] ?? 0);

    $title = trim(
        $_POST["title"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $assigned_to = !empty($_POST["assigned_to"])
        ? (int) $_POST["assigned_to"]
        : null;

    $due_date = !empty($_POST["due_date"])
        ? $_POST["due_date"]
        : null;

    $priority = $_POST["priority"] ?? "medium";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE REQUIRED FIELDS
    |--------------------------------------------------------------------------
    */

    if ($project_id <= 0) {

        $error = "Please select a project.";

    } elseif ($title === "") {

        $error = "Please enter a task title.";

    } elseif (
        !in_array(
            $priority,
            ["low", "medium", "high", "urgent"],
            true
        )
    ) {

        $error = "Invalid priority selected.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PROJECT BELONGS TO MANAGER
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $query = "
            SELECT id
            FROM projects
            WHERE id = ?
            AND manager_id = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $project_id,
                $manager_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (
                !$result ||
                mysqli_num_rows($result) !== 1
            ) {

                $error =
                    "You are not authorized to create a task for that project.";

            }

            mysqli_stmt_close($stmt);

        } else {

            $error =
                "Unable to verify the selected project.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK ASSIGNED MEMBER
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $assigned_to !== null
    ) {

        $query = "
            SELECT id
            FROM users
            WHERE id = ?
            AND role = 'member'
            AND status = 'active'
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $assigned_to
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (
                !$result ||
                mysqli_num_rows($result) !== 1
            ) {

                $error =
                    "The selected team member is invalid.";

            }

            mysqli_stmt_close($stmt);

        } else {

            $error =
                "Unable to verify the selected team member.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT TASK
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $query = "
            INSERT INTO tasks
            (
                project_id,
                title,
                description,
                assigned_to,
                created_by,
                due_date,
                priority,
                status,
                estimated_hours,
                actual_hours
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'to_do',
                0,
                0
            )
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "isssiss",
                $project_id,
                $title,
                $description,
                $assigned_to,
                $manager_id,
                $due_date,
                $priority
            );

            /*
            NOTE:
            The parameter types above need assigned_to to support NULL.
            We handle NULL separately below.
            */

            mysqli_stmt_close($stmt);


            /*
            |--------------------------------------------------------------------------
            | INSERT USING DYNAMIC NULL HANDLING
            |--------------------------------------------------------------------------
            */

            if ($assigned_to === null) {

                $query = "
                    INSERT INTO tasks
                    (
                        project_id,
                        title,
                        description,
                        assigned_to,
                        created_by,
                        due_date,
                        priority,
                        status,
                        estimated_hours,
                        actual_hours
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        NULL,
                        ?,
                        ?,
                        ?,
                        'to_do',
                        0,
                        0
                    )
                ";

                $stmt = mysqli_prepare(
                    $conn,
                    $query
                );

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "ississ",
                        $project_id,
                        $title,
                        $description,
                        $manager_id,
                        $due_date,
                        $priority
                    );

                }

            } else {

                $query = "
                    INSERT INTO tasks
                    (
                        project_id,
                        title,
                        description,
                        assigned_to,
                        created_by,
                        due_date,
                        priority,
                        status,
                        estimated_hours,
                        actual_hours
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'to_do',
                        0,
                        0
                    )
                ";

                $stmt = mysqli_prepare(
                    $conn,
                    $query
                );

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "issiiss",
                        $project_id,
                        $title,
                        $description,
                        $assigned_to,
                        $manager_id,
                        $due_date,
                        $priority
                    );

                }

            }


            if (
                isset($stmt) &&
                $stmt &&
                mysqli_stmt_execute($stmt)
            ) {

                $task_id =
                    mysqli_insert_id($conn);

                mysqli_stmt_close($stmt);


                /*
                |--------------------------------------------------------------------------
                | ACTIVITY LOG
                |--------------------------------------------------------------------------
                */

                $activity_description =
                    "Created task: " . $title;


                $activity_query = "
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
                        'task_created',
                        ?
                    )
                ";

                $activity_stmt = mysqli_prepare(
                    $conn,
                    $activity_query
                );

                if ($activity_stmt) {

                    mysqli_stmt_bind_param(
                        $activity_stmt,
                        "iis",
                        $manager_id,
                        $project_id,
                        $activity_description
                    );

                    mysqli_stmt_execute(
                        $activity_stmt
                    );

                    mysqli_stmt_close(
                        $activity_stmt
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | REDIRECT
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: manager-tasks.php?created=1"
                );

                exit;

            } else {

                if (isset($stmt) && $stmt) {

                    mysqli_stmt_close($stmt);

                }

                $error =
                    "Failed to create the task. Please try again.";

            }

        } else {

            $error =
                "Unable to prepare the task query.";

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
        Create Task | PMS
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
                href="manager-dashboard.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <a
                href="manager-projects.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▣
                </span>

                My Projects

            </a>


            <a
                href="manager-tasks.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Tasks

            </a>


            <p class="nav-title">
                WORKSPACE
            </p>


            <a
                href="manager-team.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Team

            </a>


            <a
                href="manager-reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

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
                                    $manager_name,
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $manager_name
                            ) ?>

                        </strong>


                        <span>
                            Project Manager
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
                        TASK MANAGEMENT
                    </span>


                    <h1>
                        Create New Task
                    </h1>


                    <p>
                        Create a task and assign it to a team member.
                    </p>

                </div>


                <div class="page-actions">

                    <a
                        href="manager-tasks.php"
                        class="secondary-button"
                    >
                        ← Back to Tasks
                    </a>

                </div>


            </div>



            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->

            <?php if (!empty($error)): ?>

                <div class="alert-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 FORM
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Task Information
                        </h2>


                        <p>
                            Fill in the details below.
                        </p>

                    </div>


                </div>



                <form
                    method="POST"
                    action=""
                >


                    <!-- PROJECT -->

                    <div class="form-group">


                        <label for="project_id">
                            Project
                        </label>


                        <select
                            id="project_id"
                            name="project_id"
                            required
                        >


                            <option value="">
                                Select Project
                            </option>


                            <?php foreach (
                                $projects
                                as $project
                            ): ?>


                                <option
                                    value="<?= (int)$project["id"] ?>"
                                    <?= (
                                        isset(
                                            $_POST["project_id"]
                                        )
                                        &&
                                        (int)$_POST["project_id"]
                                        ===
                                        (int)$project["id"]
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $project["name"]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <?php if (
                            empty($projects)
                        ): ?>

                            <small class="form-warning">

                                You do not have any projects
                                assigned to you yet.

                            </small>

                        <?php endif; ?>


                    </div>



                    <!-- TITLE -->

                    <div class="form-group">


                        <label for="title">
                            Task Title
                        </label>


                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="e.g. Design login page"
                            value="<?= htmlspecialchars(
                                $_POST["title"] ?? ""
                            ) ?>"
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
                            placeholder="Describe what needs to be done..."
                        ><?= htmlspecialchars(
                            $_POST["description"] ?? ""
                        ) ?></textarea>


                    </div>



                    <!-- ASSIGN TO -->

                    <div class="form-group">


                        <label for="assigned_to">
                            Assign To
                        </label>


                        <select
                            id="assigned_to"
                            name="assigned_to"
                        >


                            <option value="">
                                Unassigned
                            </option>


                            <?php foreach (
                                $members
                                as $member
                            ): ?>


                                <option
                                    value="<?= (int)$member["id"] ?>"
                                    <?= (
                                        isset(
                                            $_POST["assigned_to"]
                                        )
                                        &&
                                        (int)$_POST["assigned_to"]
                                        ===
                                        (int)$member["id"]
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $member["full_name"]
                                    ) ?>

                                    —
                                    <?= htmlspecialchars(
                                        $member["email"]
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>



                    <!-- DATE + PRIORITY -->

                    <div class="form-row">


                        <!-- DUE DATE -->

                        <div class="form-group">


                            <label for="due_date">
                                Due Date
                            </label>


                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                value="<?= htmlspecialchars(
                                    $_POST["due_date"] ?? ""
                                ) ?>"
                            >


                        </div>



                        <!-- PRIORITY -->

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
                                    <?= (
                                        ($_POST["priority"] ?? "")
                                        === "low"
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Low
                                </option>


                                <option
                                    value="medium"
                                    <?= (
                                        !isset(
                                            $_POST["priority"]
                                        )
                                        ||
                                        ($_POST["priority"] ?? "")
                                        === "medium"
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Medium
                                </option>


                                <option
                                    value="high"
                                    <?= (
                                        ($_POST["priority"] ?? "")
                                        === "high"
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    High
                                </option>


                                <option
                                    value="urgent"
                                    <?= (
                                        ($_POST["priority"] ?? "")
                                        === "urgent"
                                    )
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Urgent
                                </option>


                            </select>


                        </div>


                    </div>



                    <!-- =================================================
                         BUTTONS
                    ================================================== -->

                    <div class="modal-actions">


                        <a
                            href="manager-tasks.php"
                            class="secondary-button"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="primary-button"
                            <?= empty($projects)
                                ? "disabled"
                                : ""
                            ?>
                        >
                            Create Task
                        </button>


                    </div>


                </form>


            </div>


        </section>


    </main>


</div>


</body>

</html>