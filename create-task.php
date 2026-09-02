<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "avatar_helper.php";;
require_once "send_email_notification.php";


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["role"]) ||
    ($_SESSION["role"] !== "project_manager" && $_SESSION["role"] !== "admin")
) {

    header("Location: dashboard.php");
    exit;

}

$role = $_SESSION["role"];


$manager_id = (int) $_SESSION["user_id"];
$manager_name = $_SESSION["full_name"] ?? ($role === "admin" ? "Administrator" : "Project Manager");

$error = "";

$success = "";


/*
|--------------------------------------------------------------------------
| GET PROJECTS
|--------------------------------------------------------------------------
*/

$projects = [];

if ($role === "admin") {
    $query = "SELECT id, name FROM projects ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
    }
} else {
    $query = "SELECT id, name FROM projects WHERE manager_id = ? ORDER BY name ASC";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $manager_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $projects[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
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
    | CHECK PROJECT BELONGS TO MANAGER (or admin can access all)
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        if ($role === "admin") {
            $query = "SELECT id FROM projects WHERE id = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $project_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (!$result || mysqli_num_rows($result) !== 1) {
                    $error = "Project not found.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Unable to verify the selected project.";
            }
        } else {
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
        } /* end project ownership check */
    }    /*
    |--------------------------------------------------------------------------
    | CHECK ASSIGNED MEMBER BELONGS TO PROJECT
    | Only the project manager or project members can be assigned tasks.
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $assigned_to !== null
    ) {

        /* Check: is this user the project manager OR a project member? */
        $query = "
            SELECT 1
            FROM users u
            WHERE u.id = ?
            AND u.status = 'active'
            AND (
                u.id IN (
                    SELECT manager_id FROM projects WHERE id = ?
                )
                OR u.id IN (
                    SELECT user_id FROM project_members WHERE project_id = ?
                )
            )
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $assigned_to,
                $project_id,
                $project_id
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (
                !$result || mysqli_num_rows($result) !== 1
            ) {

                $error =
                    "That person is not a member of the selected project. Add them to the project first.";

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
                | EMAIL NOTIFICATION
                |--------------------------------------------------------------------------
                */

                send_notification_email(
                    $conn,
                    "task_created",
                    $activity_description,
                    $project_id,
                    $manager_id,
                    $task_id
                );


                /*
                |--------------------------------------------------------------------------
                | IN-APP NOTIFICATION TO ASSIGNED MEMBER
                |--------------------------------------------------------------------------
                */

                if ($assigned_to !== null && $assigned_to > 0) {

                    /* Get project name for the notification */
                    $proj_name = "";
                    $pn_stmt = mysqli_prepare($conn, "SELECT name FROM projects WHERE id = ? LIMIT 1");
                    if ($pn_stmt) {
                        mysqli_stmt_bind_param($pn_stmt, "i", $project_id);
                        mysqli_stmt_execute($pn_stmt);
                        $pn_result = mysqli_stmt_get_result($pn_stmt);
                        if ($pn_row = mysqli_fetch_assoc($pn_result)) {
                            $proj_name = $pn_row["name"];
                        }
                        mysqli_stmt_close($pn_stmt);
                    }

                    insert_user_notification(
                        $conn,
                        $assigned_to,
                        "New Task Assigned",
                        $manager_name . " assigned you the task \"" . $title . "\" in project \"" . $proj_name . "\"",
                        "task_assigned"
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | REDIRECT
                |--------------------------------------------------------------------------
                */

                $redirect = ($role === "admin") ? "tasks.php" : "manager-tasks.php";
                header(
                    "Location: $redirect?created=1"
                );

                exit;

            } else {

                if (isset($stmt) && $stmt) {

                    mysqli_stmt_close($stmt);

                }

                $error =
                    "Failed to create the task. Please try again.";

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

            <p class="nav-title">MAIN</p>

            <?php if ($role === "admin"): ?>
                <a href="admin-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="admin-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> Projects
                </a>
                <a href="tasks.php" class="nav-item active">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">MANAGEMENT</p>
                <a href="admin-users.php" class="nav-item">
                    <span class="nav-icon">♙</span> Users
                </a>
                <a href="admin-reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>
            <?php else: ?>
                <a href="manager-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="manager-projects.php" class="nav-item">
                    <span class="nav-icon">▣</span> My Projects
                </a>
                <a href="manager-tasks.php" class="nav-item active">
                    <span class="nav-icon">✓</span> Tasks
                </a>
                <p class="nav-title">WORKSPACE</p>
                <a href="manager-team.php" class="nav-item">
                    <span class="nav-icon">♙</span> Team
                </a>
                <a href="manager-reports.php" class="nav-item">
                    <span class="nav-icon">▥</span> Reports
                </a>
            <?php endif; ?>

            <p class="nav-title">ACCOUNT</p>
            <a href="profile.php" class="nav-item">
                <span class="nav-icon">◉</span> My Profile
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


                    <?= render_avatar($_SESSION["profile_image"] ?? null, $manager_name, (int)($_SESSION["user_id"])) ?>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $manager_name
                            ) ?>

                        </strong>


                        <span>
                            <?= $role === "admin" ? "Administrator" : "Project Manager" ?>
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
                        href="<?= ($role === "admin") ? "tasks.php" : "manager-tasks.php" ?>"
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
                            onchange="loadProjectMembers(this.value)"
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
                                Select a project first...
                            </option>


                        </select>

                        <small class="form-warning" id="memberLoadNote" style="display:none;">
                            No members found for this project. Add members to the project first.
                        </small>


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
                            href="<?= ($role === "admin") ? "tasks.php" : "manager-tasks.php" ?>"
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


<?php include "cookie_consent.php"; ?>
<script src="dark_mode.php"></script>
<script src="assets/js/responsive.js"></script>
<script>
function loadProjectMembers(projectId) {
    var sel = document.getElementById('assigned_to');
    var note = document.getElementById('memberLoadNote');
    if (!projectId) {
        sel.innerHTML = '<option value="">Select a project first...</option>';
        if (note) note.style.display = 'none';
        return;
    }
    sel.innerHTML = '<option value="">Loading members...</option>';
    if (note) note.style.display = 'none';
    fetch('api/project_members.php?project_id=' + projectId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var members = data.members || [];
            var html = '<option value="">Unassigned</option>';
            members.forEach(function(m) {
                html += '<option value="' + m.id + '">' + m.full_name + ' — ' + m.email + '</option>';
            });
            sel.innerHTML = html;
            if (note) note.style.display = members.length === 0 ? 'block' : 'none';
        })
        .catch(function() {
            sel.innerHTML = '<option value="">Error loading members</option>';
        });
}
/* Load members on page load if a project is already selected */
(function() {
    var projSel = document.getElementById('project_id');
    if (projSel && projSel.value) {
        loadProjectMembers(projSel.value);
    }
})();
</script>
</body>

</html>