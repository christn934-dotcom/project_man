<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| MEMBER PROTECTION|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "member") {
    header("Location: dashboard.php");
    exit;
}


$member_id = (int) $_SESSION["user_id"];
$member_name = $_SESSION["full_name"] ?? "Team Member";


/*|--------------------------------------------------------------------------| GET MEMBER'S PROJECTS|--------------------------------------------------------------------------|*/

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
        u.full_name AS manager_name
    FROM project_members pm
    INNER JOIN projects p ON pm.project_id = p.id
    INNER JOIN users u ON p.manager_id = u.id
    WHERE pm.user_id = ?
    ORDER BY p.created_at DESC
";

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $member_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
    }

    mysqli_stmt_close($stmt);
}

$total_projects = count($projects);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | PMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">P</div>
            <div><h2>PMS</h2><span>Project Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <p class="nav-title">MAIN</p>
            <a href="member-dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
            <a href="member-projects.php" class="nav-item active"><span class="nav-icon">▣</span> My Projects</a>
            <a href="member-tasks.php" class="nav-item"><span class="nav-icon">✓</span> My Tasks</a>
            <p class="nav-title">COLLABORATION</p>
            <a href="team.php" class="nav-item"><span class="nav-icon">♙</span> Team</a>
            <a href="notifications.php" class="nav-item"><span class="nav-icon">♧</span> Notifications</a>
            <p class="nav-title">SYSTEM</p>
            <a href="profile.php" class="nav-item"><span class="nav-icon">◉</span> My Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-item"><span>↪</span> Logout</a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button">☰</button>
                <div class="search-box"><span>⌕</span><input type="text" id="projectSearch" placeholder="Search projects..."></div>
            </div>
            <div class="topbar-right">
                <div class="admin-profile">
                    <div class="profile-avatar"><?= htmlspecialchars(strtoupper(substr($member_name, 0, 2))) ?></div>
                    <div class="profile-info"><strong><?= htmlspecialchars($member_name) ?></strong><span>Team Member</span></div>
                    <span class="profile-arrow">▾</span>
                </div>
            </div>
        </header>

        <section class="dashboard-content">

            <div class="page-header">
                <div>
                    <span class="page-label">PROJECTS</span>
                    <h1>My Projects</h1>
                    <p>Projects you are currently assigned to.</p>
                </div>
                <div class="page-actions">
                    <span class="project-count"><?= $total_projects ?> Project<?= $total_projects != 1 ? "s" : "" ?></span>
                </div>
            </div>

            <?php if (!empty($projects)): ?>
                <div class="manager-project-grid" id="projectsGrid">
                    <?php foreach ($projects as $project): ?>
                        <div class="manager-project-card" data-project="<?= htmlspecialchars(strtolower($project["name"])) ?>">
                            <div class="manager-card-top">
                                <div class="project-card-icon"><?= htmlspecialchars(strtoupper(substr($project["name"], 0, 1))) ?></div>
                            </div>
                            <h2><?= htmlspecialchars($project["name"]) ?></h2>
                            <p class="project-description"><?= htmlspecialchars($project["description"] ?: "No description provided.") ?></p>
                            <div class="project-card-badges">
                                <span class="status-badge status-<?= htmlspecialchars($project["status"]) ?>"><?= ucfirst(str_replace("_", " ", htmlspecialchars($project["status"]))) ?></span>
                                <span class="priority-badge priority-<?= htmlspecialchars($project["priority"]) ?>"><?= ucfirst(htmlspecialchars($project["priority"])) ?></span>
                            </div>
                            <div class="project-card-details">
                                <div>
                                    <span>Start Date</span>
                                    <strong><?= !empty($project["start_date"]) ? date("M d, Y", strtotime($project["start_date"])) : "Not set" ?></strong>
                                </div>
                                <div>
                                    <span>Deadline</span>
                                    <strong><?= !empty($project["end_date"]) ? date("M d, Y", strtotime($project["end_date"])) : "No deadline" ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-card">
                    <div class="empty-state">
                        <div class="empty-icon">▣</div>
                        <h3>No Projects Yet</h3>
                        <p>You have not been added to any projects yet.</p>
                    </div>
                </div>
            <?php endif; ?>

        </section>
    </main>

</div>

<script>
const searchInput = document.getElementById("projectSearch");
if (searchInput) {
    searchInput.addEventListener("input", function () {
        const val = this.value.toLowerCase().trim();
        document.querySelectorAll(".manager-project-card").forEach(function (card) {
            card.style.display = card.textContent.toLowerCase().includes(val) ? "" : "none";
        });
    });
}
</script>

</body>
</html>
