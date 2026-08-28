<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


$admin_name = $_SESSION["full_name"] ?? "Administrator";


/*|--------------------------------------------------------------------------| GET PROJECTS|--------------------------------------------------------------------------|*/

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
        p.created_at,
        u.full_name AS manager_name
    FROM projects p
    INNER JOIN users u
        ON p.manager_id = u.id
    ORDER BY p.created_at DESC
";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
}


/*|--------------------------------------------------------------------------| GET MANAGERS + MEMBERS|--------------------------------------------------------------------------|*/

$managers = [];

$manager_result = mysqli_query(
    $conn,
    "SELECT id, full_name, email FROM users WHERE role = 'project_manager' AND status = 'active' ORDER BY full_name ASC"
);

if ($manager_result) {
    while ($row = mysqli_fetch_assoc($manager_result)) {
        $managers[] = $row;
    }
}

$members = [];

$member_result = mysqli_query(
    $conn,
    "SELECT id, full_name, email FROM users WHERE role = 'member' AND status = 'active' ORDER BY full_name ASC"
);

if ($member_result) {
    while ($row = mysqli_fetch_assoc($member_result)) {
        $members[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>

        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="admin-dashboard.php" class="nav-item">
                <span class="nav-icon">▦</span> Dashboard
            </a>
            <a href="admin-users.php" class="nav-item">
                <span class="nav-icon">♙</span> Users
            </a>
            <a href="admin-projects.php" class="nav-item active">
                <span class="nav-icon">▣</span> Projects
            </a>
            <p class="nav-title">MANAGEMENT</p>
            <a href="admin-reports.php" class="nav-item">
                <span class="nav-icon">▥</span> Reports
            </a>
            <a href="admin-activity.php" class="nav-item">
                <span class="nav-icon">◷</span> Activity Logs
            </a>
            <p class="nav-title">ACCOUNT</p>
            <a href="profile.php" class="nav-item">
                <span class="nav-icon">◉</span> My Profile
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-item">
                <span>↪</span> Logout
            </a>
        </div>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
                <div class="search-box">
                    <span>⌕</span>
                    <input type="text" id="projectSearch" placeholder="Search projects...">
                </div>
            </div>
            <div class="topbar-right">
                <button class="notification-button" type="button">♧</button>
                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($admin_name, 0, 2))) ?></div>
                    <div class="profile-info">
                        <strong><?= htmlspecialchars($admin_name) ?></strong>
                        <span>Administrator</span>
                    </div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>


        <section class="dashboard-content">

            <?php if (isset($_GET["created"]) && $_GET["created"] == "1"): ?>
                <div class="alert-success" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;padding:12px 15px;border-radius:8px;margin-bottom:20px;">
                    ✓ Project created successfully.
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <span class="page-label">PROJECT MANAGEMENT</span>
                    <h1>Projects</h1>
                    <p>Manage all projects in the system.</p>
                </div>
                <div class="page-actions">
                    <a href="admin-create-project.php" class="primary-button">+ New Project</a>
                </div>
            </div>


            <div class="dashboard-card">
                <div class="card-header">
                    <div>
                        <h2>All Projects</h2>
                        <p><?= count($projects) ?> project(s)</p>
                    </div>
                </div>

                <?php if (count($projects) > 0): ?>
                    <div class="table-container">
                        <table class="projects-table" id="projectsTable">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Manager</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr class="clickable-row" onclick="window.location.href='manager-project-details.php?id=<?= (int)$project["id"] ?>'">
                                        <td>
                                            <div class="project-name">
                                                <div class="project-avatar"><?= htmlspecialchars(strtoupper(substr($project["name"], 0, 1))) ?></div>
                                                <div><strong><?= htmlspecialchars($project["name"]) ?></strong></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($project["manager_name"]) ?></td>
                                        <td><?= !empty($project["start_date"]) ? date("M d, Y", strtotime($project["start_date"])) : "—" ?></td>
                                        <td><?= !empty($project["end_date"]) ? date("M d, Y", strtotime($project["end_date"])) : "—" ?></td>
                                        <td><span class="priority-badge priority-<?= htmlspecialchars($project["priority"]) ?>"><?= ucfirst(htmlspecialchars($project["priority"])) ?></span></td>
                                        <td><span class="status-badge status-<?= htmlspecialchars($project["status"]) ?>"><?= ucfirst(str_replace("_", " ", htmlspecialchars($project["status"]))) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">▣</div>
                        <h3>No Projects Yet</h3>
                        <p>Create your first project to get started.</p>
                        <a href="admin-create-project.php" class="primary-button">+ Create Project</a>
                    </div>
                <?php endif; ?>
            </div>

        </section>

    </main>

</div>

<script>
const searchInput = document.getElementById("projectSearch");
if (searchInput) {
    searchInput.addEventListener("input", function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll("#projectsTable tbody tr").forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(val) ? "" : "none";
        });
    });
}
</script>

</body>
</html>
