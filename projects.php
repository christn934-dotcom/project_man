<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| Admin protection
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
| Get Project Managers
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


$manager_result = mysqli_query(
    $conn,
    $manager_query
);


if ($manager_result) {

    while (
        $row = mysqli_fetch_assoc(
            $manager_result
        )
    ) {

        $managers[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| Get Team Members
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


$member_result = mysqli_query(
    $conn,
    $member_query
);


if ($member_result) {

    while (
        $row = mysqli_fetch_assoc(
            $member_result
        )
    ) {

        $members[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| Get Projects
|--------------------------------------------------------------------------
*/

$projects = [];

$project_query = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.start_date,
        p.end_date,
        p.status,
        p.priority,
        u.full_name AS manager_name

    FROM projects p

    INNER JOIN users u
        ON p.manager_id = u.id

    ORDER BY p.created_at DESC
";


$project_result = mysqli_query(
    $conn,
    $project_query
);


if ($project_result) {

    while (
        $row = mysqli_fetch_assoc(
            $project_result
        )
    ) {

        $projects[] = $row;

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

</head>

<body>

<div class="admin-layout">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="logo-icon">
                P
            </div>

            <div>

                <h2>PMS</h2>

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
                class="nav-item active"
            >
                <span class="nav-icon">▣</span>
                Projects
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">✓</span>
                Tasks
            </a>


            <p class="nav-title">
                MANAGEMENT
            </p>

            <a href="#" class="nav-item">
                <span class="nav-icon">♙</span>
                Users
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">♚</span>
                Project Managers
            </a>

            <a href="reports.php" class="nav-item">
                <span class="nav-icon">▥</span>
                Reports
            </a>


            <p class="nav-title">
                SYSTEM
            </p>

            <a href="#" class="nav-item">
                <span class="nav-icon">⚙</span>
                Settings
            </a>

            <a href="profile.php" class="nav-item">
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


    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button class="mobile-menu">
                    ☰
                </button>

                <div class="search-box">

                    <span>⌕</span>

                    <input
                        type="text"
                        placeholder="Search projects..."
                    >

                </div>
                

            </div>


            <div class="topbar-right">

                <button class="notification-button">
                    ♧
                </button>


                <div class="admin-profile">

                    <div class="profile-avatar">
                        SA
                    </div>

                    <div class="profile-info">

                        <strong>
                            <?= htmlspecialchars($_SESSION["full_name"]) ?>
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
             PAGE
        ================================================== -->

        <section class="dashboard-content">

<?php if (isset($_GET["created"]) && $_GET["created"] == "1"): ?>

    <div class="success-alert">
        ✓ Project created successfully.
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

                </div>


                <div class="page-actions">

                    <button
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
                            <?= count($projects) ?> project(s)
                        </p>

                    </div>

                </div>


                <?php if (count($projects) > 0): ?>

                    <div class="table-container">

                        <table class="projects-table">

                            <thead>

                                <tr>

                                    <th>Project</th>

                                    <th>Manager</th>

                                    <th>Start Date</th>

                                    <th>End Date</th>

                                    <th>Priority</th>

                                    <th>Status</th>

                                    <th>Action</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($projects as $project): ?>

                                    <tr>

                                        <td>

                                            <div class="project-name">

                                                <div class="project-avatar">
                                                    <?= strtoupper(substr($project["name"], 0, 1)) ?>
                                                </div>

                                                <div>

                                                    <strong>
                                                        <?= htmlspecialchars($project["name"]) ?>
                                                    </strong>

                                                    <span>
                                                        <?= htmlspecialchars($project["description"] ?? "") ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars($project["manager_name"]) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars($project["start_date"]) ?>

                                        </td>


                                        <td>

                                            <?= $project["end_date"]
                                                ? htmlspecialchars($project["end_date"])
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <span class="priority-badge priority-<?= htmlspecialchars($project["priority"]) ?>">

                                                <?= ucfirst(htmlspecialchars($project["priority"])) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span class="status-badge status-<?= htmlspecialchars($project["status"]) ?>">

                                                <?= ucfirst(str_replace("_", " ", htmlspecialchars($project["status"]))) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <button class="table-action">
                                                ⋮
                                            </button>

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
                            No projects yet
                        </h3>

                        <p>
                            Create your first project to get started.
                        </p>

                        <button
                            class="primary-button"
                            onclick="openProjectModal()"
                        >
                            + Create Project
                        </button>

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
                class="modal-close"
                onclick="closeProjectModal()"
            >
                ×
            </button>

        </div>


        <form
            action="create-project.php"
            method="POST"
        >


            <!-- Project Name -->

            <div class="form-group">

                <label>
                    Project Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="e.g. E-Commerce Website"
                    required
                >

            </div>


            <!-- Description -->

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


            <!-- Manager -->

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

                        <option value="<?= $manager["id"] ?>">

                            <?= htmlspecialchars($manager["full_name"]) ?>

                            —
                            <?= htmlspecialchars($manager["email"]) ?>

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

            <!-- Team Members -->

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
                        value="<?= $member["id"] ?>"
                    >

                    <span class="member-avatar">
                        <?= strtoupper(
                            substr(
                                $member["full_name"],
                                0,
                                1
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


            <!-- Dates -->

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


            <!-- Priority -->

            <div class="form-group">

                <label>
                    Priority
                </label>

                <select name="priority">

                    <option value="low">
                        Low
                    </option>

                    <option value="medium" selected>
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


            <!-- Buttons -->

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
                    <?php if (count($managers) === 0) echo "disabled"; ?>
                >
                    Create Project
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openProjectModal() {

    document
        .getElementById("projectModal")
        .classList.add("show");

}


function closeProjectModal() {

    document
        .getElementById("projectModal")
        .classList.remove("show");

}


window.addEventListener("click", function(event) {

    const modal =
        document.getElementById("projectModal");

    if (event.target === modal) {

        closeProjectModal();

    }

});

</script>

</body>

</html>