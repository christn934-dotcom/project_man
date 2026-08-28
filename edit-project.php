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


/*|--------------------------------------------------------------------------| Only POST|--------------------------------------------------------------------------|*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: projects.php");
    exit;
}


/*|--------------------------------------------------------------------------| Get Form Data|--------------------------------------------------------------------------|*/

$project_id = (int) ($_POST["project_id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$description = trim($_POST["description"] ?? "");
$start_date = $_POST["start_date"] ?? "";
$end_date = $_POST["end_date"] ?? "";
$status = $_POST["status"] ?? "planning";
$priority = $_POST["priority"] ?? "medium";
$manager_id = (int) ($_POST["manager_id"] ?? 0);
$selected_members = $_POST["members"] ?? [];


/*|--------------------------------------------------------------------------| Validation|--------------------------------------------------------------------------|*/

if ($project_id <= 0) {
    die("Invalid project.");
}

if ($name === "") {
    die("Project name is required.");
}

if ($start_date === "") {
    die("Start date is required.");
}

if ($manager_id <= 0) {
    die("Please select a Project Manager.");
}

if (!in_array($status, ["planning", "in_progress", "on_hold", "completed", "cancelled"], true)) {
    die("Invalid status.");
}

if (!in_array($priority, ["low", "medium", "high", "urgent"], true)) {
    die("Invalid priority.");
}

if ($end_date !== "" && $end_date < $start_date) {
    die("End date cannot be before start date.");
}


/*|--------------------------------------------------------------------------| Verify Manager|--------------------------------------------------------------------------|*/

$check = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE id = ? AND role = 'project_manager' AND status = 'active' LIMIT 1"
);

mysqli_stmt_bind_param($check, "i", $manager_id);
mysqli_stmt_execute($check);
$manager_result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($manager_result) === 0) {
    mysqli_stmt_close($check);
    die("The selected Project Manager is invalid.");
}

mysqli_stmt_close($check);


/*|--------------------------------------------------------------------------| Update Project|--------------------------------------------------------------------------|*/

mysqli_begin_transaction($conn);

try {

    $update = mysqli_prepare(
        $conn,
        "UPDATE projects SET
            name = ?,
            description = ?,
            start_date = ?,
            end_date = NULLIF(?, ''),
            status = ?,
            priority = ?,
            manager_id = ?
        WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "ssssssii",
        $name,
        $description,
        $start_date,
        $end_date,
        $status,
        $priority,
        $manager_id,
        $project_id
    );

    if (!mysqli_stmt_execute($update)) {
        throw new Exception("Failed to update project.");
    }

    mysqli_stmt_close($update);


    /*|--------------------------------------------------------------------------| Replace Team Members|--------------------------------------------------------------------------|*/

    $delete_members = mysqli_prepare(
        $conn,
        "DELETE FROM project_members WHERE project_id = ?"
    );

    mysqli_stmt_bind_param($delete_members, "i", $project_id);

    if (!mysqli_stmt_execute($delete_members)) {
        throw new Exception("Failed to update team members.");
    }

    mysqli_stmt_close($delete_members);


    if (!empty($selected_members)) {

        $add_member = mysqli_prepare(
            $conn,
            "INSERT INTO project_members (project_id, user_id) VALUES (?, ?)"
        );

        foreach ($selected_members as $member_id) {

            $member_id = (int) $member_id;

            mysqli_stmt_bind_param($add_member, "ii", $project_id, $member_id);

            if (!mysqli_stmt_execute($add_member)) {
                throw new Exception("Failed to add team member.");
            }

        }

        mysqli_stmt_close($add_member);
    }


    /*|--------------------------------------------------------------------------| Activity Log|--------------------------------------------------------------------------|*/

    $admin_id = (int) $_SESSION["user_id"];
    $action = "project_updated";
    $log_description = "Updated project: " . $name;

    $activity = mysqli_prepare(
        $conn,
        "INSERT INTO activity_logs (user_id, project_id, action, description) VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($activity, "iiss", $admin_id, $project_id, $action, $log_description);

    if (!mysqli_stmt_execute($activity)) {
        throw new Exception("Failed to log activity.");
    }

    mysqli_stmt_close($activity);


    mysqli_commit($conn);

    header("Location: projects.php?updated=1");
    exit;


} catch (Exception $e) {

    mysqli_rollback($conn);
    die("Update failed: " . $e->getMessage());

}

?>
