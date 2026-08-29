<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| ADMIN PROTECTION
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");


/*
|--------------------------------------------------------------------------
| GET PROJECT MANAGERS
|--------------------------------------------------------------------------
*/

$managers = [];

$manager_query = "
    SELECT
        id,
        full_name,
        email
    FROM users
    WHERE role = 'project_manager'
    AND status = 'active'
    ORDER BY full_name ASC
";

$manager_result = mysqli_query($conn, $manager_query);

if ($manager_result) {

    while ($row = mysqli_fetch_assoc($manager_result)) {
        $managers[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| GET TEAM MEMBERS
|--------------------------------------------------------------------------
*/

$members = [];

$member_query = "
    SELECT
        id,
        full_name,
        email
    FROM users
    WHERE role = 'member'
    AND status = 'active'
    ORDER BY full_name ASC
";

$member_result = mysqli_query($conn, $member_query);

if ($member_result) {

    while ($row = mysqli_fetch_assoc($member_result)) {
        $members[] = $row;
    }

}


/*
|--------------------------------------------------------------------------
| GET PROJECTS
|--------------------------------------------------------------------------
*/

$projects = [];


if ($search !== "") {

    $project_query = "
        SELECT
            p.id,
            p.name,
            p.description,
            p.start_date,
            p.end_date,
            p.status,
            p.priority,
            p.created_at,
            u.full_name AS manager_name

        FROM projects p

        INNER JOIN users u
            ON p.manager_id = u.id

        WHERE
            p.name LIKE ?
            OR p.description LIKE ?
            OR u.full_name LIKE ?

        ORDER BY p.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $project_query);

    if ($stmt) {

        $search_value = "%" . $search . "%";

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $search_value,
            $search_value,
            $search_value
        );

        mysqli_stmt_execute($stmt);

        $project_result = mysqli_stmt_get_result($stmt);

        if ($project_result) {

            while ($row = mysqli_fetch_assoc($project_result)) {
                $projects[] = $row;
            }

        }

        mysqli_stmt_close($stmt);
    }

} else {

    $project_query = "
        SELECT
            p.id,
            p.name,
            p.description,
            p.start_date,
            p.end_date,
            p.status,
            p.priority,
            p.created_at,
            u.full_name AS manager_name

        FROM projects p

        INNER JOIN users u
            ON p.manager_id = u.id

        ORDER BY p.created_at DESC
    ";

    $project_result = mysqli_query($conn, $project_query);

    if ($project_result) {

        while ($row = mysqli_fetch_assoc($project_result)) {
            $projects[] = $row;
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

    <title>Projects | PMS</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /*
        |--------------------------------------------------------------------------
        | PROJECT PAGE EXTRA STYLES
        |--------------------------------------------------------------------------
        */

        .project-search-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .project-search-form input {
            min-width: 240px;
        }

        .project-search-button {
            border: none;
            cursor: pointer;
        }

        .project-description-text {
            display: block;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #777;
        }

        .action-menu {
            position: relative;
            display: inline-block;
        }

        .table-action {
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 20px;
            padding: 5px 10px;
            border-radius: 6px;
        }

        .table-action:hover {
            background: #f1f1f1;
        }

        .action-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 35px;
            min-width: 150px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.10);
            z-index: 50;
        }

        .action-dropdown.show {
            display: block;
        }

        .action-dropdown a {
            display: block;
            padding: 10px 14px;
            text-decoration: none;
            color: #333;
        }

        .action-dropdown a:hover {
            background: #f5f5f5;
        }

        .action-dropdown a.delete-action {
            color: #dc2626;
        }

        .member-selection {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px;
        }

        .member-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px;
            border-radius: 7px;
            cursor: pointer;
        }

        .member-option:hover {
            background: #f7f7f7;
        }

        .member-option input {
            width: auto;
        }

        .member-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 700;
            flex-shrink: 0;
        }

        .member-details {
            display: flex;
            flex-direction: column;
        }

        .member-details small {
            color: #777;
        }

        .no-members {
            color: #777;
            padding: 10px;
        }

        .form-warning {
            display: block;
            margin-top: 6px;
            color: #b45309;
        }

        .search-result-text {
            margin-top: 8px;
            color: #777;
            font-size: 14px;
        }

        @media (max-width: 700px) {

            .project-search-form {
                width: 100%;
            }

            .project-search-form input {
                min-width: 0;
                width: 100%;
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
                class="nav-item active"
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
                class="nav-item"
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


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-left">


                <button
                    class="mobile-menu"
                    type="button"
                >
                    ☰
                </button>


                <form
                    method="GET"
                    action="projects.php"
                    class="project-search-form"
                >

                    <div class="search-box">

                        <span>
                            ⌕
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search projects..."
                        >

                    </div>


                    <button
                        type="submit"
                        class="primary-button project-search-button"
                    >
                        Search
                    </button>


                </form>


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

                        <?= htmlspecialchars(
                            strtoupper(
                                substr(
                                    $admin_name,
                                    0,
                                    2
                                )
                            )
                        ) ?>

                    </div>


                    <div class="profile-info">

                        <strong>

                            <?= htmlspecialchars(
                                $admin_name
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


            <!-- SUCCESS MESSAGE -->

            <?php if (
                isset($_GET["created"]) &&
                $_GET["created"] == "1"
            ): ?>

                <div class="success-alert">

                    ✓ Project created successfully.

                </div>

            <?php endif; ?>


            <!-- UPDATED -->

            <?php if (
                isset($_GET["updated"]) &&
                $_GET["updated"] == "1"
            ): ?>

                <div class="success-alert">

                    ✓ Project updated successfully.

                </div>

            <?php endif; ?>


            <!-- DELETED -->

            <?php if (
                isset($_GET["deleted"]) &&
                $_GET["deleted"] == "1"
            ): ?>

                <div class="success-alert">

                    ✓ Project deleted successfully.

                </div>

            <?php endif; ?>


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        MANAGEMENT
                    </span>


                    <h1>
                        Projects
                    </h1>


                    <p>
                        Create and manage your organization's projects.
                    </p>


                    <?php if ($search !== ""): ?>

                        <div class="search-result-text">

                            Showing results for:
                            <strong>
                                <?= htmlspecialchars($search) ?>
                            </strong>

                        </div>

                    <?php endif; ?>


                </div>


                <div class="page-actions">


                    <button
                        type="button"
                        class="primary-button"
                        onclick="openProjectModal()"
                    >

                        + New Project

                    </button>


                </div>


            </div>



            <!-- PROJECT TABLE -->

            <div class="dashboard-card project-table-card">


                <div class="card-header">


                    <div>

                        <h2>
                            All Projects
                        </h2>

                        <p>

                            <?= count($projects) ?>

                            project<?= count($projects) !== 1 ? "s" : "" ?>

                        </p>

                    </div>


                    <?php if ($search !== ""): ?>

                        <a
                            href="projects.php"
                            class="text-button"
                        >
                            Clear Search
                        </a>

                    <?php endif; ?>


                </div>



                <?php if (count($projects) > 0): ?>


                    <div class="table-container">


                        <table class="projects-table">


                            <thead>

                                <tr>

                                    <th>
                                        Project
                                    </th>

                                    <th>
                                        Manager
                                    </th>

                                    <th>
                                        Start Date
                                    </th>

                                    <th>
                                        End Date
                                    </th>

                                    <th>
                                        Priority
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($projects as $project): ?>


                                    <tr>


                                        <!-- PROJECT -->

                                        <td>


                                            <div class="project-name">


                                                <div class="project-avatar">

                                                    <?= htmlspecialchars(
                                                        strtoupper(
                                                            substr(
                                                                $project["name"],
                                                                0,
                                                                1
                                                            )
                                                        )
                                                    ) ?>

                                                </div>


                                                <div>


                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $project["name"]
                                                        ) ?>

                                                    </strong>


                                                    <span
                                                        class="project-description-text"
                                                    >

                                                        <?= htmlspecialchars(
                                                            $project["description"]
                                                            ?: "No description"
                                                        ) ?>

                                                    </span>


                                                </div>


                                            </div>


                                        </td>



                                        <!-- MANAGER -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $project["manager_name"]
                                            ) ?>

                                        </td>



                                        <!-- START DATE -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $project["start_date"]
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $project["start_date"]
                                                        )
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>



                                        <!-- END DATE -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $project["end_date"]
                                                )
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $project["end_date"]
                                                        )
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>



                                        <!-- PRIORITY -->

                                        <td>


                                            <span
                                                class="priority-badge priority-<?= htmlspecialchars(
                                                    $project["priority"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        $project["priority"]
                                                    )
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- STATUS -->

                                        <td>


                                            <span
                                                class="status-badge status-<?= htmlspecialchars(
                                                    $project["status"]
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $project["status"]
                                                        )
                                                    )
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- ACTION -->

                                        <td>


                                            <div class="action-menu">


                                                <button
                                                    type="button"
                                                    class="table-action"
                                                    onclick="toggleActionMenu(<?= (int) $project["id"] ?>)"
                                                >

                                                    ⋮

                                                </button>


                                                <div
                                                    class="action-dropdown"
                                                    id="actionMenu<?= (int) $project["id"] ?>"
                                                >


                                                    <a
                                                        href="project.php?id=<?= (int) $project["id"] ?>"
                                                    >
                                                        View Project
                                                    </a>


                                                    <a
                                                        href="edit-project.php?id=<?= (int) $project["id"] ?>"
                                                    >
                                                        Edit Project
                                                    </a>


                                                    <form method="POST" action="delete-project.php" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?= (int) $project["id"] ?>">
                                                        <button type="submit" class="delete-action" onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.')" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font:inherit;">
                                                            Delete Project
                                                        </button>
                                                    </form>


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
                            ▣
                        </div>


                        <h3>

                            <?php if ($search !== ""): ?>

                                No Projects Found

                            <?php else: ?>

                                No Projects Yet

                            <?php endif; ?>

                        </h3>


                        <p>

                            <?php if ($search !== ""): ?>

                                No projects matched your search.

                            <?php else: ?>

                                Create your first project to get started.

                            <?php endif; ?>

                        </p>


                        <?php if ($search !== ""): ?>


                            <a
                                href="projects.php"
                                class="secondary-button"
                            >

                                Clear Search

                            </a>


                        <?php else: ?>


                            <button
                                type="button"
                                class="primary-button"
                                onclick="openProjectModal()"
                            >

                                + Create Project

                            </button>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<!-- =====================================================
     CREATE PROJECT MODAL
====================================================== -->

<div
    class="modal-overlay"
    id="projectModal"
>


    <div class="modal">


        <!-- MODAL HEADER -->

        <div class="modal-header">


            <div>

                <h2>
                    Create New Project
                </h2>

                <p>
                    Set up your project and assign a manager.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="closeProjectModal()"
            >
                ×
            </button>


        </div>



        <!-- CREATE FORM -->

        <form
            action="create-project.php"
            method="POST"
        >


            <!-- PROJECT NAME -->

            <div class="form-group">


                <label>
                    Project Name
                </label>


                <input
                    type="text"
                    name="name"
                    placeholder="e.g. E-Commerce Website"
                    maxlength="150"
                    required
                >


            </div>



            <!-- DESCRIPTION -->

            <div class="form-group">


                <label>
                    Description
                </label>


                <textarea
                    name="description"
                    rows="4"
                    placeholder="Describe the project..."
                ></textarea>


            </div>



            <!-- MANAGER -->

            <div class="form-group">


                <label>
                    Project Manager
                </label>


                <select
                    name="manager_id"
                    required
                >


                    <option value="">
                        Select Project Manager
                    </option>


                    <?php foreach ($managers as $manager): ?>


                        <option
                            value="<?= (int) $manager["id"] ?>"
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


                <?php if (count($managers) === 0): ?>


                    <small class="form-warning">

                        No Project Managers exist yet.
                        Create a Project Manager account first.

                    </small>


                <?php endif; ?>


            </div>



            <!-- TEAM MEMBERS -->

            <div class="form-group">


                <label>
                    Team Members
                </label>


                <div class="member-selection">


                    <?php if (count($members) > 0): ?>


                        <?php foreach ($members as $member): ?>


                            <label class="member-option">


                                <input
                                    type="checkbox"
                                    name="members[]"
                                    value="<?= (int) $member["id"] ?>"
                                >


                                <span class="member-avatar">

                                    <?= htmlspecialchars(
                                        strtoupper(
                                            substr(
                                                $member["full_name"],
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>

                                </span>


                                <span class="member-details">


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


                    <?php else: ?>


                        <p class="no-members">

                            No team members available yet.

                        </p>


                    <?php endif; ?>


                </div>


            </div>



            <!-- DATES -->

            <div class="form-row">


                <div class="form-group">


                    <label>
                        Start Date
                    </label>


                    <input
                        type="date"
                        name="start_date"
                        required
                    >


                </div>


                <div class="form-group">


                    <label>
                        End Date
                    </label>


                    <input
                        type="date"
                        name="end_date"
                    >


                </div>


            </div>



            <!-- PRIORITY -->

            <div class="form-group">


                <label>
                    Priority
                </label>


                <select
                    name="priority"
                >


                    <option value="low">
                        Low
                    </option>


                    <option
                        value="medium"
                        selected
                    >
                        Medium
                    </option>


                    <option value="high">
                        High
                    </option>


                    <option value="urgent">
                        Urgent
                    </option>


                </select>


            </div>



            <!-- BUTTONS -->

            <div class="modal-actions">


                <button
                    type="button"
                    class="secondary-button"
                    onclick="closeProjectModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="primary-button"
                    <?= count($managers) === 0 ? "disabled" : "" ?>
                >

                    Create Project

                </button>


            </div>


        </form>


    </div>


</div>



<script>

/*
|--------------------------------------------------------------------------
| OPEN PROJECT MODAL
|--------------------------------------------------------------------------
*/

function openProjectModal() {

    const modal =
        document.getElementById("projectModal");

    if (modal) {

        modal.classList.add("show");

    }

}


/*
|--------------------------------------------------------------------------
| CLOSE PROJECT MODAL
|--------------------------------------------------------------------------
*/

function closeProjectModal() {

    const modal =
        document.getElementById("projectModal");

    if (modal) {

        modal.classList.remove("show");

    }

}


/*
|--------------------------------------------------------------------------
| CLOSE MODAL WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

window.addEventListener("click", function(event) {

    const modal =
        document.getElementById("projectModal");

    if (
        modal &&
        event.target === modal
    ) {

        closeProjectModal();

    }

});


/*
|--------------------------------------------------------------------------
| ACTION MENU
|--------------------------------------------------------------------------
*/

function toggleActionMenu(projectId) {

    const menu =
        document.getElementById(
            "actionMenu" + projectId
        );

    if (!menu) {
        return;
    }


    document
        .querySelectorAll(".action-dropdown")
        .forEach(function(item) {

            if (item !== menu) {

                item.classList.remove("show");

            }

        });


    menu.classList.toggle("show");

}


/*
|--------------------------------------------------------------------------
| CLOSE ACTION MENUS WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

document.addEventListener("click", function(event) {

    if (
        !event.target.closest(".action-menu")
    ) {

        document
            .querySelectorAll(".action-dropdown")
            .forEach(function(menu) {

                menu.classList.remove("show");

            });

    }

});

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