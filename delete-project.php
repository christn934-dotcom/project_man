<?php

session_start();

require_once "config/database.php";


/*|--------------------------------------------------------------------------| Admin Protection|--------------------------------------------------------------------------|*/

require_once "auth_check.php";
require_once "send_email_notification.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: dashboard.php");
    exit;
}


/*|--------------------------------------------------------------------------| Get Project ID|--------------------------------------------------------------------------|*/

/*|--------------------------------------------------------------------------| ONLY POST|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: projects.php");
    exit;
}


$project_id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

if ($project_id <= 0) {
    header("Location: projects.php?error=invalid_project");
    exit;
}


/*|--------------------------------------------------------------------------| Check Project Exists|--------------------------------------------------------------------------|*/

$query = "SELECT id, name FROM projects WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $project_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    header("Location: projects.php?error=not_found");
    exit;
}


/*|--------------------------------------------------------------------------| Collect emails BEFORE deletion (project record will be gone after delete)|--------------------------------------------------------------------------|*/
$project_name = $project["name"];
$actor_id = (int) $_SESSION["user_id"];
$actor_name = $_SESSION["full_name"] ?? "Admin";
$delete_emails = get_project_recipient_emails($conn, $project_id, $actor_id);


/*|--------------------------------------------------------------------------| Delete Project|--------------------------------------------------------------------------|*/

mysqli_begin_transaction($conn);

try {

    /* Delete project members */
    $del = mysqli_prepare($conn, "DELETE FROM project_members WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete tasks */
    $del = mysqli_prepare($conn, "DELETE FROM tasks WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete activity logs */
    $del = mysqli_prepare($conn, "DELETE FROM activity_logs WHERE project_id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    /* Delete the project */
    $del = mysqli_prepare($conn, "DELETE FROM projects WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $project_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    mysqli_commit($conn);

    /* Activity log (project_id is gone, so log without it) */
    $log_desc = "Deleted project: " . $project_name;
    $log_action = "project_deleted";
    $lstmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    if ($lstmt) {
        mysqli_stmt_bind_param($lstmt, "iss", $actor_id, $log_action, $log_desc);
        mysqli_stmt_execute($lstmt);
        mysqli_stmt_close($lstmt);
    }

    /* EMAIL: Notify all project members + manager that project was deleted */
    if (!empty($delete_emails)) {
        $label = "Project Deleted";
        $subject = "[$label] $project_name — PROMASY";
        $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fb;padding:40px 20px;"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);"><tr><td style="background:linear-gradient(135deg,#4f46e5,#6366f1);padding:30px 40px;text-align:center;"><h1 style="margin:0;color:#fff;font-size:24px;letter-spacing:2px;">PROMASY</h1></td></tr><tr><td style="padding:30px 40px 0;text-align:center;"><div style="display:inline-block;background:#fef2f2;color:#dc2626;padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;">' . htmlspecialchars($label) . '</div></td></tr><tr><td style="padding:20px 40px;"><h2 style="margin:0 0 12px;color:#1f2937;font-size:18px;">Hello!</h2><p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.6;"><strong>' . htmlspecialchars($actor_name) . '</strong> has deleted the project <strong>"' . htmlspecialchars($project_name) . '"</strong>.</p><p style="margin:0 0 16px;color:#4b5563;font-size:14px;line-height:1.6;">All tasks and data associated with this project have been permanently removed.</p></td></tr><tr><td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #e5e7eb;"><p style="margin:0;color:#9ca3af;font-size:11px;text-align:center;">Automated notification from PROMASY</p></td></tr></table></td></tr></table></body></html>';
        $text = "$label\n" . str_repeat("=", 40) . "\n\n$actor_name has deleted the project \"$project_name\".\nAll associated tasks and data have been permanently removed.\n\n---\nAutomated notification from PROMASY.";
        foreach ($delete_emails as $email) {
            send_single_email($email, $subject, $html, $text);
        }
    }

    header("Location: projects.php?deleted=1");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);
    die("Delete failed: " . $e->getMessage());

}

?>
