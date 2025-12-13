<?php
include __DIR__ . '/db_connect.php';

// If we were redirected from the registration flow, just show a confirmation
// and skip the DB action. Registration redirects with ?created=1&wmsuid=...
$is_redirect_confirm = isset($_GET['created']) && $_GET['created'] == '1';

if (!$is_redirect_confirm) {
    // Admin credentials to set up
    $wmsuid = 'admin2025';
    $firstname = 'Admin';
    $lastname = 'User';
    $password = 'admin123';
    $usertype = 'Admin';
    $email = '';

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if admin already exists
    $check_stmt = $conn->prepare("SELECT UserID FROM Users WHERE WMSUID = ?");
    $check_stmt->bind_param("s", $wmsuid);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Update existing admin
        $update_stmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE WMSUID = ?");
        $update_stmt->bind_param("ss", $password_hash, $wmsuid);
        
        if ($update_stmt->execute()) {
            $status_html = "<h2 class='alert-success'>✓ Admin password updated successfully!</h2>";
            $status_html .= "<p>You can now login with:</p>";
            $status_html .= "<p><strong>WMSU ID:</strong> admin2025<br><strong>Password:</strong> admin123</p>";
            $status_html .= "<p><a href='index.php'>Go to Admin Login</a></p>";
        } else {
            $status_html = "<h2 class='alert-error'>✗ Error updating admin: " . htmlspecialchars($conn->error) . "</h2>";
        }
        $update_stmt->close();
    } else {
        // Create new admin
        $insert_stmt = $conn->prepare("INSERT INTO Users (WMSUID, FirstName, LastName, Email, PasswordHash, UserType) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssss", $wmsuid, $firstname, $lastname, $email, $password_hash, $usertype);
        
        if ($insert_stmt->execute()) {
            $status_html = "<h2 class='alert-success'>✓ Admin account created successfully!</h2>";
            $status_html .= "<p>You can now login with:</p>";
            $status_html .= "<p><strong>WMSU ID:</strong> admin2025<br><strong>Password:</strong> admin123</p>";
            $status_html .= "<p><a href='index.php'>Go to Admin Login</a></p>";
        } else {
            $status_html = "<h2 class='alert-error'>✗ Error creating admin: " . htmlspecialchars($conn->error) . "</h2>";
        }
        $insert_stmt->close();
    }

    $check_stmt->close();
    $conn->close();
} else {
    // Redirected from register.php
    $created_wmsuid = isset($_GET['wmsuid']) ? htmlspecialchars($_GET['wmsuid']) : '';
    $status_html = "<h2 class='alert-success'>✓ Admin account created successfully!</h2>";
    $status_html .= "<p>The new admin account <strong>" . $created_wmsuid . "</strong> has been created.</p>";
    $status_html .= "<p><a href='index.php' class='btn'>Go to Admin Login</a></p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Setup - WMSU Transport</title>
    <link rel="stylesheet" href="../user/styles/styles.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: var(--off-white, #f5f5f5); }
        .setup-container { max-width: 760px; margin: 30px auto; padding: 20px; background: var(--white, #fff); border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        h1 { color: var(--wmsu-red); margin-bottom: 6px; }
        a { color: var(--wmsu-red); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .alert-success { padding: 12px 16px; background:#e6f4ea; border:1px solid #cfead6; color:#1b6a2b; border-radius:6px; }
        .alert-error { padding: 12px 16px; background:#fff0f0; border:1px solid #ffd6d6; color:var(--wmsu-red); border-radius:6px; }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>WMSU Transport Admin Setup</h1>
        <?php echo $status_html; ?>
    </div>
</body>
</html>
