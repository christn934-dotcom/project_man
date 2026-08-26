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
        u.status

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
                        id="teamSearch"
                        placeholder="Search team members..."
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


</body>

</html>