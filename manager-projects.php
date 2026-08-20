<?php

session_start();

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Check Role
|--------------------------------------------------------------------------
*/

if ($_SESSION["role"] !== "project_manager") {
    header("Location: dashboard.php");
    exit;
}


$manager_id = $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Get Manager's Projects
|--------------------------------------------------------------------------
*/

$projects = [];

$query = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.start_date,
        p.end_date,
        p.status,
        p.priority,
        p.created_at

    FROM projects p

    WHERE p.manager_id = ?

    ORDER BY p.created_at DESC
";


$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $manager_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


while ($row = mysqli_fetch_assoc($result)) {

    $projects[] = $row;

}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Count Projects
|--------------------------------------------------------------------------
*/

$total_projects = count($projects);

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
        My Projects | PMS
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
                class="nav-item active"
            >

                <span class="nav-icon">
                    ▣
                </span>

                My Projects

            </a>


            <a
                href="manager-tasks.php"
                class="nav-item"
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
                        id="projectSearch"
                        placeholder="Search projects..."
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
             PAGE CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        PROJECTS
                    </span>

                    <h1>
                        My Projects
                    </h1>

                    <p>
                        Manage and monitor projects assigned to you.
                    </p>

                </div>


                <div class="page-actions">

                    <span class="project-count">

                        <?= $total_projects ?>

                        Project<?=
                            $total_projects != 1
                                ? "s"
                                : ""
                        ?>

                    </span>

                </div>


            </div>


            <!-- =================================================
                 PROJECT GRID
            ================================================== -->

            <?php if (!empty($projects)): ?>


                <div
                    class="manager-project-grid"
                    id="projectsGrid"
                >


                    <?php foreach ($projects as $project): ?>


                        <div
                            class="manager-project-card"
                            data-project="
                                <?= htmlspecialchars(
                                    strtolower(
                                        $project["name"]
                                    )
                                ) ?>
                        ">


                            <!-- CARD HEADER -->

                            <div class="manager-card-top">


                                <div class="project-card-icon">

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


                                <div class="manager-card-menu">

                                    <button
                                        type="button"
                                        onclick="
                                            openProjectMenu(
                                                <?= $project["id"] ?>
                                            )
                                        "
                                    >
                                        ⋮
                                    </button>

                                </div>


                            </div>


                            <!-- PROJECT NAME -->

                            <h2>

                                <?= htmlspecialchars(
                                    $project["name"]
                                ) ?>

                            </h2>


                            <!-- DESCRIPTION -->

                            <p class="project-description">

                                <?= htmlspecialchars(
                                    $project["description"]
                                    ?: "No description provided."
                                ) ?>

                            </p>


                            <!-- STATUS + PRIORITY -->

                            <div class="project-card-badges">


                                <span
                                    class="
                                        status-badge
                                        status-<?=
                                            htmlspecialchars(
                                                $project["status"]
                                            )
                                    "
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


                                <span
                                    class="
                                        priority-badge
                                        priority-<?=
                                            htmlspecialchars(
                                                $project["priority"]
                                            )
                                    "
                                >

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            $project["priority"]
                                        )
                                    ) ?>

                                </span>


                            </div>


                            <!-- DATES -->

                            <div class="project-card-details">


                                <div>

                                    <span>
                                        Start Date
                                    </span>

                                    <strong>

                                        <?= date(
                                            "M d, Y",
                                            strtotime(
                                                $project["start_date"]
                                            )
                                        ) ?>

                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Deadline
                                    </span>

                                    <strong>

                                        <?php if (
                                            !empty(
                                                $project["end_date"]
                                            )
                                        ): ?>

                                            <?= date(
                                                "M d, Y",
                                                strtotime(
                                                    $project["end_date"]
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            No deadline

                                        <?php endif; ?>

                                    </strong>

                                </div>


                            </div>


                            <!-- VIEW PROJECT -->

                            <a
                                href="manager-project-details.php?id=<?= $project["id"] ?>"
                                class="project-view-button"
                            >

                                View Project

                                <span>
                                    →
                                </span>

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="dashboard-card">


                    <div class="empty-state">


                        <div class="empty-icon">
                            ▣
                        </div>


                        <h3>
                            No Projects Yet
                        </h3>


                        <p>
                            You don't have any projects
                            assigned to you yet.
                        </p>


                    </div>


                </div>


            <?php endif; ?>


        </section>

    </main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Project Search
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById(
        "projectSearch"
    );


if (searchInput) {

    searchInput.addEventListener(
        "keyup",
        function () {

            const search =
                this.value
                    .toLowerCase()
                    .trim();


            const cards =
                document.querySelectorAll(
                    ".manager-project-card"
                );


            cards.forEach(
                function (card) {

                    const text =
                        card.textContent
                            .toLowerCase();


                    if (
                        text.includes(search)
                    ) {

                        card.style.display =
                            "";

                    } else {

                        card.style.display =
                            "none";

                    }

                }
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| Project Menu
|--------------------------------------------------------------------------
*/

function openProjectMenu(id) {

    alert(
        "Project actions for project ID "
        + id
        + " will be added later."
    );

}

</script>

</body>

</html>