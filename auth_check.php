<?php

/*|--------------------------------------------------------------------------| AUTH CHECK|--------------------------------------------------------------------------|
|
| Include this file at the top of every protected page (after session_start()
| and after requiring database.php). It checks if the user has a valid
| remember_me cookie and restores the session automatically.
|
| Usage:
|   session_start();
|   require_once "config/database.php";
|   require_once "auth_check.php";
|
|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {

    /*|--------------------------------------------------------------------------| CHECK REMEMBER ME COOKIE|--------------------------------------------------------------------------|*/

    if (isset($_COOKIE["remember_me"])) {

        $token = $_COOKIE["remember_me"];

        /* Look up the token hash in the database */

        $query = "
            SELECT rm.user_id, rm.expires_at, u.full_name, u.email, u.role
            FROM remember_me rm
            INNER JOIN users u ON rm.user_id = u.id
            WHERE rm.token = ?
            AND rm.expires_at > NOW()
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $row = mysqli_fetch_assoc($result);

                /* Regenerate session to prevent fixation */
                session_regenerate_id(true);

                /* Restore session */
                $_SESSION["user_id"]   = $row["user_id"];
                $_SESSION["full_name"] = $row["full_name"];
                $_SESSION["email"]     = $row["email"];
                $_SESSION["role"]      = $row["role"];

            } else {

                /* Token invalid or expired — clear the cookie */
                setcookie(
                    "remember_me",
                    "",
                    time() - 3600,
                    "/",
                    "",
                    false,
                    true
                );

                unset($_COOKIE["remember_me"]);

                /* Clean up expired tokens */
                mysqli_query(
                    $conn,
                    "DELETE FROM remember_me WHERE expires_at <= NOW()"
                );

            }

            mysqli_stmt_close($stmt);

        }

    }

}


/*|--------------------------------------------------------------------------| STILL NOT LOGGED IN?|--------------------------------------------------------------------------|
|
| If we still don't have a user_id in the session after the cookie check,
| redirect to login.
|
|--------------------------------------------------------------------------|*/

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}

?>
