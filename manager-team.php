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

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "project_manager"
) {

    header("Location: dashboard.php");
    exit;

}


$manager_id = (int) $_SESSION["user_id"];

$manager_name = $_SESSION["full_name"] ?? "Project Manager";


/*
|--------------------------------------------------------------------------
| GET TEAM MEMBERS
|--------------------------------------------------------------------------
|
| We get members who are assigned to projects managed
| by the currently logged-in project manager.
|
*/

$team_members = [];

$query = "
    SELECT DISTINCT
        u.id,
        u.full_name,
        u.email,
        u.status,
                        profile_image
    FROM users u

    INNER JOIN project_members pm
        ON u.id = pm.user_id

    INNER JOIN projects p
        ON pm.project_id = p.id

    WHERE p.manager_id = ?
      AND u.role = 'member'

    ORDER BY u.full_name ASC
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

            $team_members[] = $row;

        }

    }

    mysqli_stmt_close($stmt);

}


/*
|--------------------------------------------------------------------------
| GET PROJECTS FOR EACH MEMBER
|--------------------------------------------------------------------------
*/

$member_projects = [];

$query = "
    SELECT
        pm.user_id,
        p.id AS project_id,
        p.name AS project_name

    FROM project_members pm

    INNER JOIN projects p
        ON pm.project_id = p.id

    WHERE p.manager_id = ?

    ORDER BY p.name ASC
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

            $member_projects[
                $row["user_id"]
            ][] = $row;

        }

    }

    mysqli_stmt_close($stmt);

}


/*
|--------------------------------------------------------------------------
| GET TASK COUNTS
|--------------------------------------------------------------------------
*/

$member_task_counts = [];

$query = "
    SELECT
        t.assigned_to,
        COUNT(*) AS total_tasks

    FROM tasks t

    INNER JOIN projects p
        ON t.project_id = p.id

    WHERE p.manager_id = ?
      AND t.assigned_to IS NOT NULL

    GROUP BY t.assigned_to
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

            $member_task_counts[
                $row["assigned_to"]
            ] = (int) $row["total_tasks"];

        }

    }

    mysqli_stmt_close($stmt);

}


$total_members = count($team_members);

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
        My Team | PMS
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
                class="nav-item active"
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
                        id="teamSearch"
                        placeholder="Search team members..."
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



        <!-- =====================================================
             PAGE CONTENT
        ====================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="page-header">


                <div>

                    <span class="page-label">
                        WORKSPACE
                    </span>


                    <h1>
                        My Team
                    </h1>


                    <p>
                        View and manage members working on your projects.
                    </p>

                </div>


                <div class="page-actions">

                    <span class="project-count">

                        <?= $total_members ?>

                        Member<?= $total_members != 1 ? "s" : "" ?>

                    </span>

                </div>


            </div>



            <!-- =================================================
                 TEAM STAT
            ================================================== -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon">
                        ♙
                    </div>


                    <div class="stat-info">

                        <span>
                            Team Members
                        </span>


                        <strong>
                            <?= $total_members ?>
                        </strong>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 TEAM MEMBERS
            ================================================== -->

            <div class="dashboard-card">


                <div class="card-header">


                    <div>

                        <h2>
                            Team Members
                        </h2>


                        <p>
                            Members assigned to your projects
                        </p>

                    </div>


                </div>



                <?php if (!empty($team_members)): ?>


                    <div class="table-container">


                        <table
                            class="projects-table"
                            id="teamTable"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        Member
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Projects
                                    </th>

                                    <th>
                                        Tasks
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


                                <?php foreach ($team_members as $member): ?>


                                    <?php

                                    $member_id =
                                        (int) $member["id"];

                                    $projects_for_member =
                                        $member_projects[$member_id]
                                        ?? [];

                                    $task_count =
                                        $member_task_counts[$member_id]
                                        ?? 0;

                                    ?>


                                    <tr class="team-row">


                                        <!-- MEMBER -->

                                        <td>


                                            <div class="project-name">


                                                <div class="project-avatar">

                                                    <?= htmlspecialchars(
                                                        strtoupper(
                                                            substr(
                                                                $member["full_name"],
                                                                0,
                                                                2
                                                            )
                                                        )
                                                    ) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $member["full_name"]
                                                        ) ?>

                                                    </strong>


                                                    <span>
                                                        Team Member
                                                    </span>

                                                </div>


                                            </div>


                                        </td>



                                        <!-- EMAIL -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $member["email"]
                                            ) ?>

                                        </td>



                                        <!-- PROJECTS -->

                                        <td>


                                            <?php if (!empty($projects_for_member)): ?>


                                                <div class="team-projects">


                                                    <?php foreach (
                                                        $projects_for_member
                                                        as $project
                                                    ): ?>


                                                        <span class="project-tag">

                                                            <?= htmlspecialchars(
                                                                $project["project_name"]
                                                            ) ?>

                                                        </span>


                                                    <?php endforeach; ?>


                                                </div>


                                            <?php else: ?>


                                                <span>
                                                    No project
                                                </span>


                                            <?php endif; ?>


                                        </td>



                                        <!-- TASKS -->

                                        <td>

                                            <strong>
                                                <?= $task_count ?>
                                            </strong>

                                        </td>



                                        <!-- STATUS -->

                                        <td>


                                            <?php

                                            $member_status =
                                                $member["status"]
                                                ?? "active";

                                            ?>


                                            <span
                                                class="status-badge status-<?= htmlspecialchars(
                                                    $member_status
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    ucfirst(
                                                        str_replace(
                                                            "_",
                                                            " ",
                                                            $member_status
                                                        )
                                                    )
                                                ) ?>

                                            </span>


                                        </td>



                                        <!-- ACTION -->

                                        <td>


                                            <a
                                                href="manager-member-details.php?id=<?= $member_id ?>"
                                                class="table-action"
                                                title="View Member"
                                            >
                                                →
                                            </a>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <!-- EMPTY STATE -->

                    <div class="empty-state">


                        <div class="empty-icon">
                            ♙
                        </div>


                        <h3>
                            No Team Members Yet
                        </h3>


                        <p>
                            Team members assigned to your projects will appear here.
                        </p>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<!-- =====================================================
     SEARCH
====================================================== -->

<script>

const teamSearch =
    document.getElementById("teamSearch");


if (teamSearch) {

    teamSearch.addEventListener(
        "input",
        function () {


            const search =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    ".team-row"
                );


            rows.forEach(
                function (row) {


                    const text =
                        row.textContent
                            .toLowerCase();


                    if (
                        text.includes(search)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );


        }
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