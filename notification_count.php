<?php

/**
 * Notification Count API
 * 
 * Returns JSON with the unread notification count for the current user.
 * Only counts activity AFTER the user's last_seen_at timestamp.
 * Called via AJAX from the topbar notification bell.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["count" => 0]);
    exit;
}

require_once "config/database.php";

$role = $_SESSION["role"] ?? "";
$user_id = (int) $_SESSION["user_id"];

// Get the user's last_seen_at timestamp
$last_seen = null;
$stmt = mysqli_prepare($conn, "SELECT last_seen_at FROM users WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $last_seen = $row["last_seen_at"];
    }
    mysqli_stmt_close($stmt);
}

// If never seen before, use 7 days ago as fallback
if (empty($last_seen)) {
    $last_seen_clause = "a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
} else {
    $last_seen_clause = "a.created_at > '" . mysqli_real_escape_string($conn, $last_seen) . "'";
}

$count = 0;

if ($role === "admin") {

    $query = "SELECT COUNT(*) AS cnt FROM activity_logs a WHERE $last_seen_clause";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = (int) $row["cnt"];
    }

} elseif ($role === "project_manager") {

    $query = "
        SELECT COUNT(*) AS cnt
        FROM activity_logs a
        INNER JOIN projects p ON a.project_id = p.id
        WHERE p.manager_id = ?
        AND $last_seen_clause
    ";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $count = (int) $row["cnt"];
        }
        mysqli_stmt_close($stmt);
    }

} else {

    $query = "
        SELECT COUNT(*) AS cnt
        FROM activity_logs a
        INNER JOIN projects p ON a.project_id = p.id
        WHERE p.id IN (
            SELECT project_id FROM project_members WHERE user_id = ?
        )
        AND $last_seen_clause
    ";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $count = (int) $row["cnt"];
        }
        mysqli_stmt_close($stmt);
    }

}

echo json_encode(["count" => $count]);
