<?php

session_start();

require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| Admin Protection
|--------------------------------------------------------------------------
*/

require_once "auth_check.php";
require_once "send_email_notification.php";

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: dashboard.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: project.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Get Form Data
|--------------------------------------------------------------------------
*/

$name = trim($_POST["name"] ?? "");

$description = trim(
    $_POST["description"] ?? ""
);

$manager_id = (int)(
    $_POST["manager_id"] ?? 0
);

$members = $_POST["members"] ?? [];

$start_date = $_POST["start_date"] ?? "";

$end_date = $_POST["end_date"] ?? "";

$priority = $_POST["priority"] ?? "medium";

$created_by = (int)$_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Validate Project Name
|--------------------------------------------------------------------------
*/

if ($name === "") {

    header("Location: projects.php?error=" . urlencode("Project name is required."));
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate Manager
|--------------------------------------------------------------------------
*/

if ($manager_id <= 0) {

    header("Location: projects.php?error=" . urlencode("Please select a Project Manager."));
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate Start Date
|--------------------------------------------------------------------------
*/

if ($start_date === "") {

    header("Location: projects.php?error=" . urlencode("Please select a start date."));
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate Priority
|--------------------------------------------------------------------------
*/

$allowed_priorities = [
    "low",
    "medium",
    "high",
    "urgent"
];

if (
    !in_array(
        $priority,
        $allowed_priorities,
        true
    )
) {

    header("Location: projects.php?error=" . urlencode("Invalid priority."));
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate End Date
|--------------------------------------------------------------------------
*/

if (
    $end_date !== "" &&
    $end_date < $start_date
) {

    header("Location: projects.php?error=" . urlencode("End date cannot be before start date."));
    exit;

}


/*
|--------------------------------------------------------------------------
| Verify Project Manager
|--------------------------------------------------------------------------
*/

$manager_check = mysqli_prepare(
    $conn,
    "
    SELECT id
    FROM users
    WHERE id = ?
    AND role = 'project_manager'
    AND status = 'active'
    LIMIT 1
    "
);


mysqli_stmt_bind_param(
    $manager_check,
    "i",
    $manager_id
);


mysqli_stmt_execute(
    $manager_check
);


$manager_result =
    mysqli_stmt_get_result(
        $manager_check
    );


if (
    mysqli_num_rows(
        $manager_result
    ) === 0
) {

    mysqli_stmt_close(
        $manager_check
    );

    header("Location: projects.php?error=" . urlencode("The selected Project Manager is invalid."));
    exit;

}


mysqli_stmt_close(
    $manager_check
);


/*
|--------------------------------------------------------------------------
| Verify Team Members
|--------------------------------------------------------------------------
*/

if (!empty($members)) {

    foreach ($members as $member_id) {

        $member_id = (int)$member_id;


        $member_check = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM users
            WHERE id = ?
            AND role = 'member'
            AND status = 'active'
            LIMIT 1
            "
        );


        mysqli_stmt_bind_param(
            $member_check,
            "i",
            $member_id
        );


        mysqli_stmt_execute(
            $member_check
        );


        $member_result =
            mysqli_stmt_get_result(
                $member_check
            );


        if (
            mysqli_num_rows(
                $member_result
            ) === 0
        ) {

            mysqli_stmt_close(
                $member_check
            );

            header("Location: projects.php?error=" . urlencode("One of the selected team members is invalid."));
            exit;

        }


        mysqli_stmt_close(
            $member_check
        );

    }

}


/*
|--------------------------------------------------------------------------
| Start Database Transaction
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction(
    $conn
);


try {


    /*
    |--------------------------------------------------------------------------
    | Create Project
    |--------------------------------------------------------------------------
    */

    $project_query = mysqli_prepare(
        $conn,
        "
        INSERT INTO projects
        (
            name,
            description,
            start_date,
            end_date,
            status,
            priority,
            manager_id,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NULLIF(?, ''),
            'planning',
            ?,
            ?,
            ?
        )
        "
    );


    mysqli_stmt_bind_param(
        $project_query,
        "sssssii",
        $name,
        $description,
        $start_date,
        $end_date,
        $priority,
        $manager_id,
        $created_by
    );


    if (
        !mysqli_stmt_execute(
            $project_query
        )
    ) {

        throw new Exception(
            "Failed to create project."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Get New Project ID
    |--------------------------------------------------------------------------
    */

    $project_id =
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $project_query
    );


    /*
    |--------------------------------------------------------------------------
    | Add Team Members
    |--------------------------------------------------------------------------
    */

    if (!empty($members)) {

        $member_query = mysqli_prepare(
            $conn,
            "
            INSERT INTO project_members
            (
                project_id,
                user_id
            )
            VALUES
            (
                ?,
                ?
            )
            "
        );


        foreach ($members as $member_id) {

            $member_id =
                (int)$member_id;


            mysqli_stmt_bind_param(
                $member_query,
                "ii",
                $project_id,
                $member_id
            );


            if (
                !mysqli_stmt_execute(
                    $member_query
                )
            ) {

                throw new Exception(
                    "Failed to add team member."
                );

            }

        }


        mysqli_stmt_close(
            $member_query
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */

    $action =
        "project_created";

    $activity_description =
        "Created project: " . $name;


    $activity_query =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO activity_logs
            (
                user_id,
                project_id,
                action,
                description
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
            "
        );


    mysqli_stmt_bind_param(
        $activity_query,
        "iiss",
        $created_by,
        $project_id,
        $action,
        $activity_description
    );


    if (
        !mysqli_stmt_execute(
            $activity_query
        )
    ) {

        throw new Exception(
            "Failed to create activity log."
        );

    }


    mysqli_stmt_close(
        $activity_query
    );


    /*
    |--------------------------------------------------------------------------
    | EMAIL NOTIFICATION
    |--------------------------------------------------------------------------
    */

    send_notification_email(
        $conn,
        "project_created",
        $activity_description,
        $project_id,
        $created_by
    );


    /*
    |--------------------------------------------------------------------------
    | Commit Everything
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conn
    );


    /*
    |--------------------------------------------------------------------------
    | Return to Projects
    |--------------------------------------------------------------------------
    */
header(
    "Location: projects.php?created=1"
);

exit;


} catch (Exception $e) {


    /*
    |--------------------------------------------------------------------------
    | Undo Everything If Something Fails
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conn
    );


    die(
        "Project creation failed: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>