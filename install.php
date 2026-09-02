<?php

/**
 * PROMASY — One-Click Installer
 * 
 * Upload all files to 000webhost, then visit:
 *   http://your-site.000webhostapp.com/install.php
 * 
 * This script will:
 *   1. Create the config/env.php with your database credentials
 *   2. Import all tables from database.sql
 *   3. Create the default admin account
 *   4. Redirect you to the login page
 */

$step = $_GET["step"] ?? "form";
$error = "";
$success = "";

/* ================================================================
   STEP: FORM — Collect database credentials
   ================================================================ */
if ($step === "form"): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install PROMASY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-card { background: #fff; border-radius: 16px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .install-header { text-align: center; margin-bottom: 30px; }
        .install-header h1 { font-size: 28px; color: #1f2937; margin-bottom: 8px; }
        .install-header p { color: #6b7280; font-size: 14px; }
        .logo-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; margin-bottom: 15px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; color: #374151; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #4f46e5; }
        .form-group small { display: block; margin-top: 4px; color: #9ca3af; font-size: 12px; }
        .install-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .install-btn:hover { opacity: 0.9; }
        .info-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 13px; color: #1e40af; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <div class="logo-icon">P</div>
            <h1>PROMASY Installer</h1>
            <p>Enter your 000webhost database credentials to install.</p>
        </div>

        <div class="info-box">
            <strong>Where to find these:</strong><br>
            1. Go to your 000webhost Control Panel<br>
            2. Click <strong>"Database Manager"</strong> or <strong>"MySQL Databases"</strong><br>
            3. Copy the database name, username, and password from there
        </div>

        <form method="GET" action="install.php">
            <input type="hidden" name="step" value="import">

            <div class="form-group">
                <label>Database Host</label>
                <input type="text" name="host" value="localhost" required>
                <small>Usually "localhost" on 000webhost</small>
            </div>

            <div class="form-group">
                <label>Database Name</label>
                <input type="text" name="dbname" placeholder="e.g., id1234567_promasy" required>
            </div>

            <div class="form-group">
                <label>Database Username</label>
                <input type="text" name="dbuser" placeholder="e.g., id1234567_promasy" required>
            </div>

            <div class="form-group">
                <label>Database Password</label>
                <input type="password" name="dbpass" placeholder="Your database password" required>
            </div>

            <div class="form-group">
                <label>Admin Email (for default admin account)</label>
                <input type="email" name="admin_email" placeholder="admin@example.com" required>
            </div>

            <div class="form-group">
                <label>Admin Password (for default admin account)</label>
                <input type="password" name="admin_pass" placeholder="Min 6 characters" required minlength="6">
            </div>

            <button type="submit" class="install-btn">🚀 Install PROMASY</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
endif;


/* ================================================================
   STEP: IMPORT — Run the SQL and create admin user
   ================================================================ */

if ($step === "import"):

    $host   = trim($_GET["host"]   ?? "localhost");
    $dbname = trim($_GET["dbname"] ?? "");
    $dbuser = trim($_GET["dbuser"] ?? "");
    $dbpass = $_GET["dbpass"] ?? "";
    $admin_email = trim($_GET["admin_email"] ?? "");
    $admin_pass  = $_GET["admin_pass"] ?? "";

    if (empty($dbname) || empty($dbuser) || empty($admin_email) || empty($admin_pass)) {
        header("Location: install.php?step=form&error=" . urlencode("All fields are required."));
        exit;
    }

    if (strlen($admin_pass) < 6) {
        header("Location: install.php?step=form&error=" . urlencode("Admin password must be at least 6 characters."));
        exit;
    }

    /* Connect */
    $conn = @mysqli_connect($host, $dbuser, $dbpass, $dbname);
    if (!$conn) {
        header("Location: install.php?step=form&error=" . urlencode("Database connection failed: " . mysqli_connect_error()));
        exit;
    }

    mysqli_set_charset($conn, "utf8mb4");

    /* Read SQL file */
    $sql_file = __DIR__ . "/database.sql";
    if (!file_exists($sql_file)) {
        header("Location: install.php?step=form&error=" . urlencode("database.sql not found. Please make sure it's in the root folder."));
        exit;
    }

    $sql_content = file_get_contents($sql_file);

    /* Execute SQL — split by semicolons, handle multi-line statements */
    /* Remove MySQL version-specific comments that might cause errors */
    $sql_content = preg_replace('/\/\*!\d+\s+/', '/* ', $sql_content);

    /* Split into individual statements */
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));

    $imported = 0;
    $errors = [];
    foreach ($statements as $stmt) {
        if (empty($stmt) || preg_match('/^--/', $stmt) || preg_match('/^\/\*/', $stmt)) {
            continue;
        }
        if (mysqli_query($conn, $stmt)) {
            $imported++;
        } else {
            $err = mysqli_error($conn);
            /* Skip "table already exists" errors — that's fine */
            if (strpos($err, "already exists") === false) {
                $errors[] = $err;
            }
        }
    }

    /* Create env.php */
    $env_content = "<?php\n\$db_host = " . var_export($host, true) . ";\n\$db_user = " . var_export($dbuser, true) . ";\n\$db_pass = " . var_export($dbpass, true) . ";\n\$db_name = " . var_export($dbname, true) . ";\n?>\n";

    $env_path = __DIR__ . "/config/env.php";
    if (!file_put_contents($env_path, $env_content)) {
        $errors[] = "Could not write config/env.php — check folder permissions.";
    }

    /* Create admin user */
    $admin_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
    $admin_name = "Administrator";

    /* Check if admin already exists */
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = " . mysqli_real_escape_string($conn, $admin_email) . " LIMIT 1");
    if ($check && mysqli_num_rows($check) === 0) {
        $insert = "INSERT INTO users (full_name, email, password, role, status, created_at) VALUES (" .
            mysqli_real_escape_string($conn, $admin_name) . ", " .
            mysqli_real_escape_string($conn, $admin_email) . ", " .
            mysqli_real_escape_string($conn, $admin_hash) . ", " .
            "'admin', 'active', NOW())";
        if (!mysqli_query($conn, $insert)) {
            $errors[] = "Failed to create admin user: " . mysqli_error($conn);
        }
    }

    /* Try to add last_seen_at column if it doesn't exist */
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_seen_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");

    mysqli_close($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Complete | PROMASY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-card { background: #fff; border-radius: 16px; padding: 40px; max-width: 500px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; }
        .success-icon { width: 80px; height: 80px; background: #ecfdf5; color: #059669; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px; }
        .error-icon { width: 80px; height: 80px; background: #fef2f2; color: #dc2626; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 20px; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        p { color: #6b7280; margin-bottom: 15px; font-size: 14px; line-height: 1.6; }
        .error-list { text-align: left; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; margin: 15px 0; font-size: 13px; color: #991b1b; }
        .login-btn { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; margin-top: 10px; }
        .login-btn:hover { opacity: 0.9; }
        .retry-btn { display: inline-block; padding: 14px 32px; background: #374151; color: #fff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="install-card">
        <?php if (empty($errors)): ?>
            <div class="success-icon">✓</div>
            <h1>Installation Complete!</h1>
            <p>Your PROMASY project management system is now installed and ready to use.</p>
            <p><strong>Database:</strong> <?= htmlspecialchars($dbname) ?><br>
            <strong>Admin:</strong> <?= htmlspecialchars($admin_email) ?></p>
            <a href="login.php" class="login-btn">Login to PROMASY →</a>
        <?php else: ?>
            <div class="error-icon">✕</div>
            <h1>Installation Completed with Warnings</h1>
            <p>The database was imported and your account was created, but there were some issues:</p>
            <div class="error-list">
                <?php foreach ($errors as $err): ?>
                    <div>• <?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
            <p>Your admin account was created. Try logging in:</p>
            <a href="login.php" class="login-btn">Login to PROMASY →</a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php exit; endif; ?>
