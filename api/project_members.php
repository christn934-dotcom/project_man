<?php
/**
 * Project Members API
 * 
 * Returns JSON list of members belonging to a specific project.
 * Used by the task creation form to filter assignees by project.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../auth_check.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["members" => []]);
    exit;
}

$project_id = (int) ($_GET["project_id"] ?? 0);

if ($project_id <= 0) {
    echo json_encode(["members" => []]);
    exit;
}

/* Get the project manager (they can also be assigned tasks) */
$manager_query = "
    SELECT u.id, u.full_name, u.email, u.role
    FROM projects p
    INNER JOIN users u ON p.manager_id = u.id
    WHERE p.id = ? AND u.status = 'active'
    LIMIT 1
";
$mstmt = mysqli_prepare($conn, $manager_query);
mysqli_stmt_bind_param($mstmt, "i", $project_id);
mysqli_stmt_execute($mstmt);
$mresult = mysqli_stmt_get_result($mstmt);

$members = [];
$seen_ids = [];

if ($mrow = mysqli_fetch_assoc($mresult)) {
    $members[] = [
        "id"        => (int) $mrow["id"],
        "full_name" => $mrow["full_name"],
        "email"     => $mrow["email"],
        "role"      => $mrow["role"],
    ];
    $seen_ids[] = (int) $mrow["id"];
}
mysqli_stmt_close($mstmt);

/* Get project members */
$member_query = "
    SELECT u.id, u.full_name, u.email, u.role
    FROM project_members pm
    INNER JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ? AND u.status = 'active'
    ORDER BY u.full_name ASC
";
$mstmt2 = mysqli_prepare($conn, $member_query);
mysqli_stmt_bind_param($mstmt2, "i", $project_id);
mysqli_stmt_execute($mstmt2);
$mresult2 = mysqli_stmt_get_result($mstmt2);

while ($row = mysqli_fetch_assoc($mresult2)) {
    $uid = (int) $row["id"];
    if (!in_array($uid, $seen_ids, true)) {
        $members[] = [
            "id"        => $uid,
            "full_name" => $row["full_name"],
            "email"     => $row["email"],
            "role"      => $row["role"],
        ];
        $seen_ids[] = $uid;
    }
}
mysqli_stmt_close($mstmt2);

echo json_encode(["members" => $members]);
