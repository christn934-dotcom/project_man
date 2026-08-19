<?php

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| Default Admin Information
|--------------------------------------------------------------------------
*/

$full_name = "System Administrator";
$email = "admin@pms.com";
$password = "Admin@123";
$role = "admin";

/*
|--------------------------------------------------------------------------
| Check if an Admin already exists
|--------------------------------------------------------------------------
*/

$check = mysqli_prepare(
    $conn,
    "SELECT id FROM users WHERE role = 'admin' LIMIT 1"
);

mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);

if (mysqli_num_rows($result) > 0) {

    echo "<h2>Admin already exists.</h2>";
    echo "<p>No new admin account was created.</p>";

    exit;
}

/*
|--------------------------------------------------------------------------
| Hash Password
|--------------------------------------------------------------------------
*/

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| Insert Admin
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users (full_name, email, password, role)
     VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $full_name,
    $email,
    $hashed_password,
    $role
);

if (mysqli_stmt_execute($stmt)) {

    echo "<h2>Admin account created successfully!</h2>";

    echo "<p><strong>Name:</strong> $full_name</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Role:</strong> $role</p>";

    echo "<br>";
    echo "<p>You can now use these credentials to log in.</p>";

} else {

    echo "<h2>Error creating admin.</h2>";
    echo "<p>" . mysqli_error($conn) . "</p>";
}

?>