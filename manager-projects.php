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

if (!isset($_SESSION["role"])) {

    header("Location: login.php");
    exit;

}

if ($_SESSION["role"] !== "project_manager") {

    if ($_SESSION["role"] === "admin") {

        header("Location: admin-dashboard.php");
        exit;

    }

    if ($_SESSION["role"] === "member") {

        header("Location: member-dashboard.php");
        exit;

    }

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| MANAGER INFORMATION
|--------------------------------------------------------------------------
*/

$manager_id = (int) $_SESSION["user_id"];

$manager_name = $_SESSION["full_name"] ?? "Project Manager";


/*
|--------------------------------------------------------------------------
| GET MANAGER'S PROJECTS
|--------------------------------------------------------------------------
|
| A project manager can only see projects where they are
| assigned as the project's manager.
|
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
| COUNT PROJECTS
|--------------------------------------------------------------------------
*/

$total_projects = count($projects);


/*
|--------------------------------------------------------------------------
| MANAGER INITIALS
|--------------------------------------------------------------------------
*/

$manager_initials = strtoupper(
    substr(
        $manager_name,
        0,
        2
    )
);

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
<script>
(function(){var t=localStorage.getItem('promasy-theme');if(t==='dark'){document.body.classList.add('dark');document.body.classList.remove('light')}else if(t==='light'){document.body.classList.add('light');document.body.classList.remove('dark')}})();
</script>


<div class="admin-layout">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

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


        <!-- NAVIGATION -->

        <nav class="sidebar-nav">


            <p class="nav-title">
                MAIN
            </p>


            <!-- DASHBOARD -->

            <a
                href="manager-dashboard.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▦
                </span>

                Dashboard

            </a>


            <!-- PROJECTS -->

            <a
                href="manager-projects.php"
                class="nav-item active"
            >

                <span class="nav-icon">
                    ▣
                </span>

                My Projects

            </a>


            <!-- TASKS -->

            <a
                href="manager-tasks.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ✓
                </span>

                Tasks

            </a>


            <!-- WORKSPACE -->

            <p class="nav-title">
                WORKSPACE
            </p>


            <!-- TEAM -->

            <a
                href="manager-team.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ♙
                </span>

                Team

            </a>


            <!-- REPORTS -->

            <a
                href="manager-reports.php"
                class="nav-item"
            >

                <span class="nav-icon">
                    ▥
                </span>

                Reports

            </a>


            <!-- ACCOUNT -->

            <p class="nav-title">
                ACCOUNT
            </p>


            <!-- PROFILE -->

                        <a
                href="notifications.php"
                class="nav-item"
            >
                <span class="nav-icon">♧</span>
                Notifications
            </a>

            <a
                href="manager-settings.php"
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


        <!-- LOGOUT -->

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


                <!-- MOBILE MENU -->

                <button
                    class="mobile-menu"
                    type="button"
                >
                    ☰
                </button>


                <!-- SEARCH -->

                <div class="search-box">

                    <span>
                        ⌕
                    </span>

                    <input
                        type="text"
                        id="projectSearch"
                        placeholder="Search projects..."
                        autocomplete="off"
                    >

                </div>


            </div>


            <!-- TOPBAR RIGHT -->

            <div class="topbar-right">


                <!-- NOTIFICATIONS -->

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


                <!-- PROFILE -->

                <div class="admin-profile">


                    <?= render_avatar($_SESSION["profile_image"] ?? null, $manager_name, (int)($_SESSION["user_id"])) ?>


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



        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

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


                        <!-- PROJECT CARD -->

                        <div
                            class="manager-project-card"
                            data-project="<?= htmlspecialchars(
                                strtolower(
                                    $project["name"]
                                )
                            ) ?>"
                        >


                            <!-- CARD HEADER -->

                            <div class="manager-card-top">


                                <!-- PROJECT ICON -->

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


                                <!-- PROJECT MENU -->

                                <div class="manager-card-menu">


                                    <button
                                        type="button"
                                        onclick="openProjectMenu(
                                            <?= (int) $project["id"] ?>
                                        )"
                                        aria-label="Project options"
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


                                <!-- STATUS -->

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


                                <!-- PRIORITY -->

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


                            </div>



                            <!-- DATES -->

                            <div class="project-card-details">


                                <!-- START DATE -->

                                <div>


                                    <span>
                                        Start Date
                                    </span>


                                    <strong>

                                        <?= !empty(
                                            $project["start_date"]
                                        )
                                            ? date(
                                                "M d, Y",
                                                strtotime(
                                                    $project["start_date"]
                                                )
                                            )
                                            : "Not set"
                                        ?>

                                    </strong>


                                </div>



                                <!-- DEADLINE -->

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
                                href="manager-project-details.php?id=<?= (int) $project["id"] ?>"
                                class="project-view-button"
                            >

                                View Project


                                <span>
                                    →
                                </span>

                            </a>

                            <a
                                href="manager-edit-project.php?id=<?= (int) $project["id"] ?>"
                                class="project-view-button"
                                style="background:#4f46e5; color:#fff; margin-left:8px;"
                            >

                                Edit Project

                                <span>
                                    →
                                </span>

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


                <!-- NO SEARCH RESULTS -->

                <div
                    id="noSearchResults"
                    class="dashboard-card"
                    style="display: none;"
                >

                    <div class="empty-state">


                        <div class="empty-icon">
                            ⌕
                        </div>


                        <h3>
                            No Projects Found
                        </h3>


                        <p>
                            No project matches your search.
                        </p>


                    </div>

                </div>


            <?php else: ?>


                <!-- =================================================
                     EMPTY STATE
                ================================================== -->

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



<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| PROJECT SEARCH
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById(
        "projectSearch"
    );


const projectCards =
    document.querySelectorAll(
        ".manager-project-card"
    );


const noSearchResults =
    document.getElementById(
        "noSearchResults"
    );


if (searchInput) {


    searchInput.addEventListener(
        "input",
        function () {


            const search =
                this.value
                    .toLowerCase()
                    .trim();


            let visibleCards = 0;


            projectCards.forEach(
                function (card) {


                    const text =
                        card.textContent
                            .toLowerCase();


                    if (
                        text.includes(search)
                    ) {


                        card.style.display =
                            "";


                        visibleCards++;


                    } else {


                        card.style.display =
                            "none";


                    }

                }
            );


            /*
            |--------------------------------------------------------------------------
            | SHOW NO RESULTS MESSAGE
            |--------------------------------------------------------------------------
            */

            if (noSearchResults) {


                if (
                    search !== "" &&
                    visibleCards === 0
                ) {

                    noSearchResults.style.display =
                        "block";

                } else {

                    noSearchResults.style.display =
                        "none";

                }

            }

        }
    );

}



/*
|--------------------------------------------------------------------------
| PROJECT MENU
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